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
    class Dblib
    {
        public const ATTR_CONNECTION_TIMEOUT = PDO::DBLIB_ATTR_CONNECTION_TIMEOUT;
        public const ATTR_QUERY_TIMEOUT = PDO::DBLIB_ATTR_QUERY_TIMEOUT;
        public const ATTR_STRINGIFY_UNIQUEIDENTIFIER = PDO::DBLIB_ATTR_STRINGIFY_UNIQUEIDENTIFIER;
        public const ATTR_VERSION = PDO::DBLIB_ATTR_VERSION;
        public const ATTR_TDS_VERSION = \PHP_VERSION_ID >= 70300 ? PDO::DBLIB_ATTR_TDS_VERSION : 1004;
        public const ATTR_SKIP_EMPTY_ROWSETS = \PHP_VERSION_ID >= 70300 ? PDO::DBLIB_ATTR_SKIP_EMPTY_ROWSETS : 1005;
        public const ATTR_DATETIME_CONVERT = \PHP_VERSION_ID >= 70300 ? \PDO::DBLIB_ATTR_DATETIME_CONVERT : 1006;
    }
}
