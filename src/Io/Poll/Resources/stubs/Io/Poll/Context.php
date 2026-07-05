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
        private Backend $backend;

        /**
         * The fd table of the emulated poll backend, keyed by stream resource id.
         *
         * @var array<int, Watcher>
         */
        private array $watchers = [];

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

        public function add(Handle $handle, array $events, mixed $data = null): Watcher
        {
            if (!$handle instanceof \StreamPollHandle) {
                throw new InvalidHandleException(\sprintf('Handle of type "%s" is not supported by the polyfill; only %s is.', \get_class($handle), \StreamPollHandle::class));
            }

            $stream = $handle->getStream();
            if (!\is_resource($stream) || 'MEMORY' === stream_get_meta_data($stream)['stream_type']) {
                throw new InvalidHandleException('Invalid handle for polling');
            }

            static $normalize, $create;
            $normalize ??= \Closure::bind(
                static fn (array $events, string $method, int $argument): array => Watcher::normalizeEvents($events, $method, $argument),
                null,
                Watcher::class,
            );
            $create ??= \Closure::bind(
                static fn (\WeakReference $ctx, Handle $h, array $e, mixed $d): Watcher => new Watcher($ctx, $h, $e, $d),
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

            return $this->watchers[$id] = $create(\WeakReference::create($this), $handle, $events, $data);
        }

        public function wait(?int $timeoutSeconds = null, int $timeoutMicroseconds = 0, ?int $maxEvents = null): array
        {
            if (null !== $timeoutSeconds) {
                if ($timeoutSeconds < 0) {
                    throw new \ValueError(\sprintf('%s(): Argument #1 ($timeoutSeconds) must be greater than or equal to 0', __METHOD__));
                }
                if ($timeoutMicroseconds < 0) {
                    throw new \ValueError(\sprintf('%s(): Argument #2 ($timeoutMicroseconds) must be greater than or equal to 0', __METHOD__));
                }
            }

            if (null !== $maxEvents && $maxEvents <= 0) {
                throw new \ValueError(\sprintf('%s(): Argument #3 ($maxEvents) must be greater than 0', __METHOD__));
            }

            // Like poll() reporting POLLNVAL, drop watchers whose stream has been
            // closed; poll() returns immediately in that case, without sleeping
            $evicted = false;
            foreach ($this->watchers as $id => $watcher) {
                if (!\is_resource($watcher->getHandle()->getStream())) {
                    unset($this->watchers[$id]);
                    $evicted = true;
                }
            }

            if (!$this->watchers) {
                if (!$evicted && null !== $timeoutSeconds && 0 < $micros = $timeoutSeconds * 1_000_000 + $timeoutMicroseconds) {
                    usleep($micros);
                }

                return [];
            }

            $read = $write = [];
            foreach ($this->watchers as $id => $watcher) {
                $stream = $watcher->getHandle()->getStream();

                // poll() reports hang-ups and errors even when not requested;
                // detecting them requires select() readability for every stream,
                // at the cost of spurious wake-ups that the retry loop below absorbs
                $read[$id] = $stream;

                if (\in_array(Event::Write, $watcher->getWatchedEvents(), true)) {
                    $write[$id] = $stream;
                }
            }

            if ($evicted) {
                $deadline = 0;
            } elseif (null !== $timeoutSeconds) {
                $deadline = hrtime(true) + ($timeoutSeconds * 1_000_000 + $timeoutMicroseconds) * 1000;
            } else {
                $deadline = null;
            }

            $triggered = [];
            while (true) {
                $r = $read;
                $w = $write;
                $e = null;

                if (null === $deadline) {
                    $result = @stream_select($r, $w, $e, null);
                } else {
                    $remaining = max(0, $deadline - hrtime(true));
                    $result = @stream_select($r, $w, $e, \intdiv($remaining, 1_000_000_000), \intdiv($remaining % 1_000_000_000, 1000));
                }

                if (false === $result) {
                    throw new FailedPollWaitException('Poll wait failed');
                }

                if (0 === $result) {
                    return [];
                }

                foreach ($this->watchers as $id => $watcher) {
                    $isReadable = isset($r[$id]);
                    $isWritable = isset($w[$id]);

                    if (!$isReadable && !$isWritable) {
                        continue;
                    }

                    $stream = $watcher->getHandle()->getStream();
                    $watched = $watcher->getWatchedEvents();
                    $hangUp = $error = false;
                    $readable = $isReadable;

                    if ($isReadable) {
                        $peek = @stream_socket_recvfrom($stream, 1, \STREAM_PEEK);
                        $meta = stream_get_meta_data($stream);
                        if (false === $peek) {
                            if (str_contains($meta['stream_type'], 'socket')) {
                                // peeking a readable socket only fails when the connection
                                // was reset (peer closed with unread data): POLLERR|POLLHUP
                                $error = $hangUp = true;
                            } elseif (!$meta['seekable'] && feof($stream)) {
                                // a pipe at EOF raises POLLHUP without POLLIN,
                                // while a regular file always raises plain POLLIN
                                $hangUp = true;
                                $readable = false;
                            }
                        } elseif ('' === $peek && !\in_array($meta['stream_type'], ['udp_socket', 'udg_socket'], true)) {
                            // a stream socket at EOF raises POLLIN|POLLHUP,
                            // while an empty datagram is plain POLLIN
                            $hangUp = true;
                        }
                    }

                    $events = [];
                    if ($readable && \in_array(Event::Read, $watched, true)) {
                        $events[] = Event::Read;
                    }
                    if ($isWritable && \in_array(Event::Write, $watched, true)) {
                        $events[] = Event::Write;
                    }
                    if ($error) {
                        $events[] = Event::Error; // reported even when not watched, like POLLERR
                    }
                    if ($hangUp) {
                        $events[] = Event::HangUp; // reported even when not watched, like POLLHUP
                    }

                    if ($events) {
                        $triggered[] = [$watcher, $events];
                        if (null !== $maxEvents && \count($triggered) === $maxEvents) {
                            break;
                        }
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
                usleep(1000);
            }

            static $setTriggered;
            $setTriggered ??= \Closure::bind(
                static fn (Watcher $w, array $events) => $w->triggeredEvents = $events,
                null,
                Watcher::class,
            );

            $result = [];
            foreach ($triggered as [$watcher, $events]) {
                $setTriggered($watcher, $events);
                $result[] = $watcher;

                if (\in_array(Event::OneShot, $watcher->getWatchedEvents(), true)) {
                    // like the native backend, only the fd entry is dropped; the watcher stays active
                    unset($this->watchers[get_resource_id($watcher->getHandle()->getStream())]);
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
