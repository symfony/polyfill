<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Io\Poll;

if (\PHP_VERSION_ID < 80600) {
    final class Context
    {
        private const IS_SOCKET = 1;
        private const IS_SEEKABLE = 2;
        private const IS_DATAGRAM = 4;

        private Backend $backend;

        /**
         * The fd table of the emulated poll backend, keyed by stream resource id.
         *
         * @var array<int, Watcher>
         */
        private array $watchers = [];

        /**
         * The select() sets, kept in sync with the fd table so that wait() does not
         * have to walk every watcher on each call.
         *
         * $selectRead holds every watched stream, because poll() reports hang-ups and
         * errors even when they were not requested and detecting them requires
         * select() readability; $wantsRead tells which of them asked for Event::Read.
         *
         * @var array<int, resource>
         */
        private array $selectRead = [];

        /** @var array<int, resource> */
        private array $selectWrite = [];

        /** @var array<int, true> */
        private array $wantsRead = [];

        /**
         * Traits of each watched stream that hang-up detection needs, as a bitmask of
         * self::IS_SOCKET, self::IS_SEEKABLE and self::IS_DATAGRAM. They never change
         * for a given stream, so they are read once instead of on every wake-up.
         *
         * @var array<int, int>
         */
        private array $streamTraits = [];

        public function __construct(Backend $backend = Backend::Auto)
        {
            if (isset($this->backend)) {
                throw new \Error('Io\Poll\Context object is already constructed');
            }

            if (!$backend->isAvailable()) {
                throw new BackendUnavailableException(\sprintf('Backend %s not available', $backend->name));
            }

            $this->backend = Backend::Auto === $backend ? Backend::Poll : $backend;
        }

        public function __destruct()
        {
            static $deactivate;
            $deactivate ??= \Closure::bind(
                static function (Watcher $w): void {
                    $w->active = false;
                    $w->context = null;
                },
                null,
                Watcher::class,
            );

            foreach ($this->watchers as $watcher) {
                $deactivate($watcher);
            }
        }

        public function getBackend(): Backend
        {
            return $this->backend;
        }

        /**
         * Keeps the select() sets in sync with the fd table.
         *
         * @param resource|null $stream Null to drop the entry
         */
        private function sync(int $key, mixed $stream, array $events = []): void
        {
            if (null === $stream) {
                unset($this->selectRead[$key], $this->selectWrite[$key], $this->wantsRead[$key], $this->streamTraits[$key]);

                return;
            }

            $this->selectRead[$key] = $stream;

            if (\in_array(Event::Read, $events, true)) {
                $this->wantsRead[$key] = true;
            } else {
                unset($this->wantsRead[$key]);
            }

            if (\in_array(Event::Write, $events, true)) {
                $this->selectWrite[$key] = $stream;
            } else {
                unset($this->selectWrite[$key]);
            }
        }

        /**
         * Drops the watchers whose stream has been closed, as poll() drops the fds it reports POLLNVAL for.
         */
        private function evictClosed(): int
        {
            $evicted = 0;

            foreach ($this->watchers as $key => $watcher) {
                if (!\is_resource($watcher->getHandle()->getStream())) {
                    unset($this->watchers[$key]);
                    $this->sync($key, null);
                    ++$evicted;
                }
            }

            return $evicted;
        }

        public function add(Handle $handle, array $events, mixed $data = null): Watcher
        {
            if (!\method_exists($handle, 'getStream')) {
                throw new InvalidHandleException(\sprintf('Handle of type "%s" is not supported by the polyfill; it should expose the stream to poll through a getStream() method, as %s does.', \get_class($handle), \StreamPollHandle::class));
            }

            $stream = $handle->getStream();
            if (!\is_resource($stream) || 'MEMORY' === ($meta = stream_get_meta_data($stream))['stream_type']) {
                throw new InvalidHandleException('Invalid handle for polling');
            }

            static $normalize, $create;
            $normalize ??= \Closure::bind(
                static fn (array $events, string $method, int $argument): array => Watcher::normalizeEvents($events, $method, $argument),
                null,
                Watcher::class,
            );
            $create ??= \Closure::bind(
                static fn (\WeakReference $ctx, int $k, Handle $h, array $e, mixed $d): Watcher => new Watcher($ctx, $k, $h, $e, $d),
                null,
                Watcher::class,
            );

            $events = $normalize($events, __METHOD__, 2);

            if (\in_array(Event::EdgeTriggered, $events, true)) {
                throw new FailedHandleAddException('Failed to add handle', FailedPollOperationException::ERROR_NOSUPPORT);
            }

            $id = get_resource_id($stream);
            if (null !== $existing = $this->watchers[$id] ?? null) {
                if (\is_resource($existing->getHandle()->getStream())) {
                    throw new HandleAlreadyWatchedException('Handle already added');
                }
                unset($this->watchers[$id]); // the resource id was recycled from a closed stream
            }

            $this->sync($id, $stream, $events);
            $this->streamTraits[$id] = (str_contains($meta['stream_type'], 'socket') ? self::IS_SOCKET : 0)
                | ($meta['seekable'] ? self::IS_SEEKABLE : 0)
                | (\in_array($meta['stream_type'], ['udp_socket', 'udg_socket'], true) ? self::IS_DATAGRAM : 0);

            return $this->watchers[$id] = $create(\WeakReference::create($this), $id, $handle, $events, $data);
        }

        public function wait(?\Time\Duration $timeout = null, ?int $maxEvents = null): array
        {
            if (null !== $timeout && $timeout->negative) {
                throw new \ValueError(\sprintf('%s(): Argument #1 ($timeout) must not be negative', __METHOD__));
            }

            if (null !== $maxEvents && $maxEvents <= 0) {
                throw new \ValueError(\sprintf('%s(): Argument #2 ($maxEvents) must be greater than 0', __METHOD__));
            }

            $timeoutNanoseconds = null !== $timeout ? $timeout->seconds * 1_000_000_000 + $timeout->nanoseconds : null;

            if (!$this->watchers) {
                if (null !== $timeoutNanoseconds && 0 < $micros = \intdiv($timeoutNanoseconds, 1000)) {
                    usleep($micros);
                }

                return [];
            }

            $read = $this->selectRead;
            $write = $this->selectWrite;
            $deadline = null !== $timeoutNanoseconds ? hrtime(true) + $timeoutNanoseconds : null;

            $triggered = $parked = $park = [];
            while (true) {
                $r = $read;
                $w = $write;
                $e = null;

                error_clear_last();

                try {
                    if (null === $deadline) {
                        $result = @stream_select($r, $w, $e, null);
                    } else {
                        $remaining = max(0, $deadline - hrtime(true));
                        $result = @stream_select($r, $w, $e, \intdiv($remaining, 1_000_000_000), \intdiv($remaining % 1_000_000_000, 1000));
                    }
                } catch (\TypeError|\ValueError $selectError) {
                    // Like poll() reporting POLLNVAL, drop the watchers whose stream has been closed
                    // and report what the others have to say at once, without sleeping
                    if (!$this->evictClosed()) {
                        throw $selectError;
                    }

                    if (!$this->watchers) {
                        return [];
                    }

                    $read = $this->selectRead;
                    $write = $this->selectWrite;
                    $parked = [];
                    $deadline = 0;

                    continue;
                }

                if (false === $result) {
                    // "stream_select(): Unable to select [4]: Interrupted system call (max_fd=5)"
                    $errno = preg_match('/ \[(\d+)\]: /', error_get_last()['message'] ?? '', $m) ? (int) $m[1] : 0;

                    // like the native backends, which map errno through php_poll_errno_to_error()
                    throw new FailedPollWaitException('Poll wait failed', match ($errno) {
                        4 => FailedPollOperationException::ERROR_INTERRUPTED, // EINTR
                        9, 22 => FailedPollOperationException::ERROR_INVALID, // EBADF, EINVAL
                        12 => FailedPollOperationException::ERROR_NOMEM, // ENOMEM
                        default => FailedPollOperationException::ERROR_SYSTEM,
                    });
                }

                if (0 === $result) {
                    return [];
                }

                foreach ($r + $w as $id => $stream) {
                    if (null === $watcher = $this->watchers[$id] ?? null) {
                        continue;
                    }

                    $isWritable = isset($w[$id]); // only watchers that asked for Event::Write are selected
                    // a parked stream is only looked at again when it turns writable,
                    // which is when it could carry an error or a hang-up
                    $isReadable = isset($r[$id]) || isset($parked[$id]);

                    $hangUp = $error = false;
                    $readable = $isReadable;

                    if ($isReadable) {
                        $traits = $this->streamTraits[$id];
                        $peek = @stream_socket_recvfrom($stream, 1, \STREAM_PEEK);
                        if (false === $peek) {
                            if ($traits & self::IS_SOCKET) {
                                // peeking a readable socket only fails when the connection
                                // was reset (peer closed with unread data): POLLERR|POLLHUP
                                $error = $hangUp = true;
                            } elseif (!($traits & self::IS_SEEKABLE) && feof($stream)) {
                                // a pipe at EOF raises POLLHUP without POLLIN,
                                // while a regular file always raises plain POLLIN
                                $hangUp = true;
                                $readable = false;
                            }
                        } elseif ('' === $peek && !($traits & self::IS_DATAGRAM)) {
                            // a stream socket at EOF raises POLLIN|POLLHUP,
                            // while an empty datagram is plain POLLIN
                            $hangUp = true;
                        }
                    }

                    $events = [];
                    if ($readable && isset($this->wantsRead[$id])) {
                        $events[] = Event::Read;
                    }
                    if ($isWritable) {
                        $events[] = Event::Write;
                    }
                    if ($error) {
                        $events[] = Event::Error; // reported even when not watched, like POLLERR
                    }
                    if ($hangUp) {
                        $events[] = Event::HangUp; // reported even when not watched, like POLLHUP
                    }

                    if ($events) {
                        $triggered[$id] = [$watcher, $events];
                        if (null !== $maxEvents && \count($triggered) === $maxEvents) {
                            break;
                        }
                    } elseif (isset($r[$id], $write[$id])) {
                        $park[$id] = true;
                    }
                }

                if ($triggered) {
                    break;
                }

                // Readiness that maps to no watched event; poll() would not have
                // woken up, so keep waiting for the remaining timeout
                if (null !== $deadline && hrtime(true) >= $deadline) {
                    return [];
                }

                if (!$park) {
                    usleep(1000);

                    continue;
                }

                // Pending data keeps select() returning at once while poll() would sleep.
                // Since nothing can consume it until wait() returns, stop selecting these
                // streams for reading: their writability, which is what's watched here,
                // still wakes us up. The cost is a hang-up on them being reported only
                // once they turn writable, which a hang-up does
                $parked += $park;
                $read = array_diff_key($read, $park);
                $park = [];
            }

            static $setTriggered;
            $setTriggered ??= \Closure::bind(
                static fn (Watcher $w, array $events) => $w->triggeredEvents = $events,
                null,
                Watcher::class,
            );

            $result = [];
            foreach ($triggered as $id => [$watcher, $events]) {
                $setTriggered($watcher, $events);
                $result[] = $watcher;

                if (\in_array(Event::OneShot, $watcher->getWatchedEvents(), true)) {
                    // like the native backend, only the fd entry is dropped; the watcher stays active
                    unset($this->watchers[$id]);
                    $this->sync($id, null);
                }
            }

            return $result;
        }

        public function __clone()
        {
            throw new \Error(\sprintf('Trying to clone an uncloneable object of class %s', self::class));
        }

        public function __debugInfo(): array
        {
            return [];
        }

        public function __serialize(): array
        {
            throw new \Exception(\sprintf("Serialization of '%s' is not allowed", self::class));
        }

        public function __unserialize(array $data): void
        {
            throw new \Exception(\sprintf("Unserialization of '%s' is not allowed", self::class));
        }
    }
}
