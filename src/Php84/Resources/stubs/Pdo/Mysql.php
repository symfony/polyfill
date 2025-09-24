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
    class Mysql
    {
        public const ATTR_COMPRESS = PDO::MYSQL_ATTR_COMPRESS;
        public const ATTR_DIRECT_QUERY = PDO::MYSQL_ATTR_DIRECT_QUERY;
        public const ATTR_FOUND_ROWS = PDO::MYSQL_ATTR_FOUND_ROWS;
        public const ATTR_IGNORE_SPACE = PDO::MYSQL_ATTR_IGNORE_SPACE;
        public const ATTR_INIT_COMMAND = PDO::MYSQL_ATTR_INIT_COMMAND;
        public const ATTR_LOCAL_INFILE = PDO::MYSQL_ATTR_LOCAL_INFILE;
        public const ATTR_LOCAL_INFILE_DIRECTORY = \PHP_VERSION_ID >= 80100 ? \PDO::MYSQL_ATTR_LOCAL_INFILE_DIRECTORY : 1015;
        public const ATTR_MAX_BUFFER_SIZE = PDO::MYSQL_ATTR_MAX_BUFFER_SIZE;
        public const ATTR_MULTI_STATEMENTS = PDO::MYSQL_ATTR_MULTI_STATEMENTS;
        public const ATTR_READ_DEFAULT_FILE = PDO::MYSQL_ATTR_READ_DEFAULT_FILE;
        public const ATTR_READ_DEFAULT_GROUP = PDO::MYSQL_ATTR_READ_DEFAULT_GROUP;
        public const ATTR_SERVER_PUBLIC_KEY = PDO::MYSQL_ATTR_SERVER_PUBLIC_KEY;
        public const ATTR_SSL_CA = PDO::MYSQL_ATTR_SSL_CA;
        public const ATTR_SSL_CAPATH = PDO::MYSQL_ATTR_SSL_CAPATH;
        public const ATTR_SSL_CERT = PDO::MYSQL_ATTR_SSL_CERT;
        public const ATTR_SSL_CIPHER = PDO::MYSQL_ATTR_SSL_CIPHER;
        public const ATTR_SSL_KEY = PDO::MYSQL_ATTR_SSL_KEY;
        public const ATTR_SSL_VERIFY_SERVER_CERT = PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT;
        public const ATTR_USE_BUFFERED_QUERY = PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;
    }
}
