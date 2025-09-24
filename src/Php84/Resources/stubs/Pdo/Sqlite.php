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
        public const ATTR_EXTENDED_RESULT_CODES = PDO::SQLITE_ATTR_EXTENDED_RESULT_CODES;
        public const ATTR_OPEN_FLAGS = PDO::SQLITE_ATTR_OPEN_FLAGS;
        public const ATTR_READONLY_STATEMENT = PDO::SQLITE_ATTR_READONLY_STATEMENT;
        public const DETERMINISTIC = PDO::SQLITE_DETERMINISTIC;
        public const OPEN_READONLY = PDO::SQLITE_OPEN_READONLY;
        public const OPEN_READWRITE = PDO::SQLITE_OPEN_READWRITE;
        public const OPEN_CREATE = PDO::SQLITE_OPEN_CREATE;
    }
}
