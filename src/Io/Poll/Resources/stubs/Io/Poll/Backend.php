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
    enum Backend
    {
        case Auto;
        case Poll;
        case Epoll;
        case Kqueue;
        case EventPorts;
        case WSAPoll;

        public function isAvailable(): bool
        {
            return match ($this) {
                self::Auto, self::Poll => true,
                default => false,
            };
        }

        public function supportsEdgeTriggering(): bool
        {
            return false;
        }

        public static function getAvailableBackends(): array
        {
            return [self::Poll];
        }
    }
}
