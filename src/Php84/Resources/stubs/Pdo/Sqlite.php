<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Pdo;

use PDO;

if (\PHP_VERSION_ID < 80400) {
    class Sqlite
    {
        public const ATTR_EXTENDED_RESULT_CODES = \PHP_VERSION_ID >= 70400 ? \PDO::SQLITE_ATTR_EXTENDED_RESULT_CODES : 1002;
        public const ATTR_OPEN_FLAGS = \PHP_VERSION_ID >= 70300 ? \PDO::SQLITE_ATTR_OPEN_FLAGS : 1000;
        public const ATTR_READONLY_STATEMENT = \PHP_VERSION_ID >= 70400 ? \PDO::SQLITE_ATTR_READONLY_STATEMENT : 1001;
        public const DETERMINISTIC = PDO::SQLITE_DETERMINISTIC;
        public const OPEN_READONLY = \PHP_VERSION_ID >= 70300 ? \PDO::SQLITE_OPEN_READONLY : 1;
        public const OPEN_READWRITE = \PHP_VERSION_ID >= 70300 ? \PDO::SQLITE_OPEN_READWRITE : 2;
        public const OPEN_CREATE = \PHP_VERSION_ID >= 70300 ? \PDO::SQLITE_OPEN_CREATE : 4;
    }
}
