<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (\PHP_VERSION_ID < 80600) {
    final class StreamPollHandle implements Io\Poll\Handle
    {
        private $stream;

        public function __construct($stream)
        {
            if (!is_resource($stream) || ('stream' !== $type = get_resource_type($stream)) && 'persistent stream' !== $type) {
                throw new TypeError(sprintf('%s(): Argument #1 ($stream) must be an open stream resource', __METHOD__));
            }

            if (null !== $this->stream) {
                throw new Error('StreamPollHandle object is already constructed');
            }

            $this->stream = $stream;
        }

        public function getStream()
        {
            return $this->stream;
        }

        public function isValid(): bool
        {
            return is_resource($this->stream) && !feof($this->stream);
        }

        public function __clone()
        {
            throw new Error(sprintf('Trying to clone an uncloneable object of class %s', self::class));
        }

        public function __debugInfo(): array
        {
            return [];
        }

        public function __serialize(): array
        {
            throw new Exception(sprintf("Serialization of '%s' is not allowed", self::class));
        }

        public function __unserialize(array $data): void
        {
            throw new Exception(sprintf("Unserialization of '%s' is not allowed", self::class));
        }
    }
}
