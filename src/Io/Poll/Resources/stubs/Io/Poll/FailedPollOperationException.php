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
    abstract class FailedPollOperationException extends PollException
    {
        public const ERROR_NONE = 0;
        public const ERROR_SYSTEM = 1;
        public const ERROR_NOMEM = 2;
        public const ERROR_INVALID = 3;
        public const ERROR_EXISTS = 4;
        public const ERROR_NOTFOUND = 5;
        public const ERROR_TIMEOUT = 6;
        public const ERROR_INTERRUPTED = 7;
        public const ERROR_PERMISSION = 8;
        public const ERROR_TOOBIG = 9;
        public const ERROR_AGAIN = 10;
        public const ERROR_NOSUPPORT = 11;
    }
}
