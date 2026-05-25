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
    final class Watcher
    {
        private bool $active = true;
        private array $triggeredEvents = [];

        /**
         * @param \WeakReference<Context>|null $context Weak so that dropping the last userland
         *                                              reference to the context destroys it and
         *                                              deactivates its watchers, like native
         */
        private function __construct(
            private ?\WeakReference $context,
            private readonly Handle $handle,
            private array $events,
            private mixed $data,
        ) {
        }

        public function getHandle(): Handle
        {
            return $this->handle;
        }

        public function getWatchedEvents(): array
        {
            return $this->events;
        }

        public function getTriggeredEvents(): array
        {
            return $this->triggeredEvents;
        }

        public function getData(): mixed
        {
            return $this->data;
        }

        public function hasTriggered(Event $event): bool
        {
            return \in_array($event, $this->triggeredEvents, true);
        }

        public function isActive(): bool
        {
            return $this->active;
        }

        public function modify(array $events, mixed $data = null): void
        {
            $this->applyEvents(self::normalizeEvents($events, __METHOD__, 1));

            if (1 < \func_num_args()) {
                $this->data = $data;
            }
        }

        public function modifyEvents(array $events): void
        {
            $this->applyEvents(self::normalizeEvents($events, __METHOD__, 1));
        }

        public function modifyData(mixed $data): void
        {
            if (!$this->active) {
                throw new InactiveWatcherException('Cannot modify inactive watcher');
            }

            $this->data = $data;
        }

        public function remove(): void
        {
            $context = $this->active ? $this->context?->get() : null;
            if (null === $context) {
                throw new InactiveWatcherException('Cannot remove inactive watcher');
            }

            static $detach;
            $detach ??= \Closure::bind(
                static function (Context $ctx, Watcher $w): void {
                    if (false !== $id = \array_search($w, $ctx->watchers, true)) {
                        unset($ctx->watchers[$id]);
                    }
                },
                null,
                Context::class,
            );
            $detach($context, $this);
            $this->active = false;
            $this->context = null;
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

        private function applyEvents(array $events): void
        {
            $context = $this->active ? $this->context?->get() : null;
            if (null === $context) {
                throw new InactiveWatcherException('Cannot modify inactive watcher');
            }

            if (!$this->handle instanceof \StreamPollHandle || !\is_resource($this->handle->getStream())) {
                throw new InvalidHandleException('Invalid handle for polling');
            }

            if (\in_array(Event::EdgeTriggered, $events, true)) {
                throw new FailedWatcherModificationException('Failed to modify watcher in polling system', FailedPollOperationException::ERROR_NOSUPPORT);
            }

            static $contains;
            $contains ??= \Closure::bind(
                static fn (Context $ctx, Watcher $w): bool => \in_array($w, $ctx->watchers, true),
                null,
                Context::class,
            );
            if (!$contains($context, $this)) {
                throw new FailedWatcherModificationException('Failed to modify watcher in polling system', FailedPollOperationException::ERROR_NOTFOUND);
            }

            $this->events = $events;
        }

        /**
         * Native stores events as a bitmask: duplicates collapse and getWatchedEvents()
         * returns the cases in declaration order.
         */
        private static function normalizeEvents(array $events, string $method, int $argument): array
        {
            foreach ($events as $event) {
                if (!$event instanceof Event) {
                    throw new \TypeError(\sprintf('%s(): Argument #%d ($events) must be array of Event enums', $method, $argument));
                }
            }

            if (!$events) {
                throw new \TypeError(\sprintf('%s(): Argument #%d ($events) must be array of Event enums', $method, $argument));
            }

            $normalized = [];
            foreach (Event::cases() as $case) {
                if (\in_array($case, $events, true)) {
                    $normalized[] = $case;
                }
            }

            return $normalized;
        }
    }
}
