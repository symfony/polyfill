<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php84;

use PHPUnit\Framework\TestCase;

class PdoTest extends TestCase
{
    /**
     * @requires extension pdo_dblib
     * @requires extension pdo_sqlite
     */
    public function testDblibConstructor()
    {
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage("Pdo\Dblib::__construct() cannot be used for connecting to the \"sqlite\" driver");
        new \Pdo\Dblib('sqlite:');
    }

    /**
     * @requires extension pdo_firebird
     * @requires extension pdo_sqlite
     */
    public function testFirebirdConstructor()
    {
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage("Pdo\Firebird::__construct() cannot be used for connecting to the \"sqlite\" driver");
        new \Pdo\Firebird('sqlite:');
    }

    /**
     * @requires extension pdo_mysql
     * @requires extension pdo_sqlite
     */
    public function testMysqlConstructor()
    {
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage("Pdo\Mysql::__construct() cannot be used for connecting to the \"sqlite\" driver");
        new \Pdo\Mysql('sqlite:');
    }

    /**
     * @requires extension pdo_odbc
     * @requires extension pdo_sqlite
     */
    public function testOdbcConstructor()
    {
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage("Pdo\Odbc::__construct() cannot be used for connecting to the \"sqlite\" driver");
        new \Pdo\Odbc('sqlite:');
    }

    /**
     * @requires extension pdo_pgsql
     * @requires extension pdo_sqlite
     */
    public function testPgsqlConstructor()
    {
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage("Pdo\Pgsql::__construct() cannot be used for connecting to the \"sqlite\" driver");
        new \Pdo\Pgsql('sqlite:');
    }

    /**
     * @requires extension pdo_sqlite
     */
    public function testSqliteConstructor()
    {
        $sqlite = new \Pdo\Sqlite('sqlite:');
        $this->assertInstanceOf(\Pdo\Sqlite::class, $sqlite);
    }

    /**
     * @requires extension pdo_dblib
     * @requires extension pdo_sqlite
     */
    public function testDblibConnect()
    {
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage("Pdo\Dblib::connect() cannot be used for connecting to the \"sqlite\" driver");
        \Pdo\Dblib::connect('sqlite:');
    }

    /**
     * @requires extension pdo_firebird
     * @requires extension pdo_sqlite
     */
    public function testFirebirdConnect()
    {
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage("Pdo\Firebird::connect() cannot be used for connecting to the \"sqlite\" driver");
        \Pdo\Firebird::connect('sqlite:');
    }

    /**
     * @requires extension pdo_mysql
     * @requires extension pdo_sqlite
     */
    public function testMysqlConnect()
    {
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage("Pdo\Mysql::connect() cannot be used for connecting to the \"sqlite\" driver");
        \Pdo\Mysql::connect('sqlite:');
    }

    /**
     * @requires extension pdo_odbc
     * @requires extension pdo_sqlite
     */
    public function testOdbcConnect()
    {
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage("Pdo\Odbc::connect() cannot be used for connecting to the \"sqlite\" driver");
        \Pdo\Odbc::connect('sqlite:');
    }

    /**
     * @requires extension pdo_pgsql
     * @requires extension pdo_sqlite
     */
    public function testPgsqlConnect()
    {
        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage("Pdo\Pgsql::connect() cannot be used for connecting to the \"sqlite\" driver");
        \Pdo\Pgsql::connect('sqlite:');
    }

    /**
     * @requires extension pdo_sqlite
     */
    public function testSqliteConnect()
    {
        $sqlite = \Pdo\Sqlite::connect('sqlite:');
        $this->assertInstanceOf(\Pdo\Sqlite::class, $sqlite);
    }

    /**
     * @requires extension pdo_sqlite
     */
    public function testSqliteConnectOnClassExtensions()
    {
        $sqlite = ExtendedPdoSqlite::connect('sqlite:');
        $this->assertInstanceOf(\Pdo\Sqlite::class, $sqlite);
    }

    /**
     * @requires extension pdo_dblib
     */
    public function testDblibConstants()
    {
        $this->assertSame(1000, \Pdo\Dblib::ATTR_CONNECTION_TIMEOUT);
        $this->assertSame(1001, \Pdo\Dblib::ATTR_QUERY_TIMEOUT);
        $this->assertSame(1002, \Pdo\Dblib::ATTR_STRINGIFY_UNIQUEIDENTIFIER);
        $this->assertSame(1003, \Pdo\Dblib::ATTR_VERSION);
        $this->assertSame(1004, \Pdo\Dblib::ATTR_TDS_VERSION);
        $this->assertSame(1005, \Pdo\Dblib::ATTR_SKIP_EMPTY_ROWSETS);
        $this->assertSame(1006, \Pdo\Dblib::ATTR_DATETIME_CONVERT);
    }

    /**
     * @requires extension pdo_firebird
     */
    public function testFirebirdConstants()
    {
        $this->assertSame(1000, \Pdo\Firebird::ATTR_DATE_FORMAT);
        $this->assertSame(1001, \Pdo\Firebird::ATTR_TIME_FORMAT);
        $this->assertSame(1002, \Pdo\Firebird::ATTR_TIMESTAMP_FORMAT);
    }

    /**
     * @requires extension pdo_mysql
     */
    public function testMysqlConstants()
    {
        $this->assertSame(1000, \Pdo\Mysql::ATTR_USE_BUFFERED_QUERY);
        $this->assertSame(1001, \Pdo\Mysql::ATTR_LOCAL_INFILE);
        $this->assertSame(1002, \Pdo\Mysql::ATTR_INIT_COMMAND);
        $this->assertSame(1003, \Pdo\Mysql::ATTR_COMPRESS);
        $this->assertSame(\PHP_VERSION_ID < 80500 ? 1004 : 20, \Pdo\Mysql::ATTR_DIRECT_QUERY);
        $this->assertSame(\PHP_VERSION_ID < 80500 ? 1005 : 1004, \Pdo\Mysql::ATTR_FOUND_ROWS);
        $this->assertSame(\PHP_VERSION_ID < 80500 ? 1006 : 1005, \Pdo\Mysql::ATTR_IGNORE_SPACE);
        $this->assertSame(\PHP_VERSION_ID < 80500 ? 1007 : 1006, \Pdo\Mysql::ATTR_SSL_KEY);
        $this->assertSame(\PHP_VERSION_ID < 80500 ? 1008 : 1007, \Pdo\Mysql::ATTR_SSL_CERT);
        $this->assertSame(\PHP_VERSION_ID < 80500 ? 1009 : 1008, \Pdo\Mysql::ATTR_SSL_CA);
        $this->assertSame(\PHP_VERSION_ID < 80500 ? 1010 : 1009, \Pdo\Mysql::ATTR_SSL_CAPATH);
        $this->assertSame(\PHP_VERSION_ID < 80500 ? 1011 : 1010, \Pdo\Mysql::ATTR_SSL_CIPHER);
        $this->assertSame(\PHP_VERSION_ID < 80500 ? 1012 : 1011, \Pdo\Mysql::ATTR_SERVER_PUBLIC_KEY);
        $this->assertSame(\PHP_VERSION_ID < 80500 ? 1013 : 1012, \Pdo\Mysql::ATTR_MULTI_STATEMENTS);
        $this->assertSame(\PHP_VERSION_ID < 80500 ? 1015 : 1014, \Pdo\Mysql::ATTR_LOCAL_INFILE_DIRECTORY);
        if (\defined('PDO::MYSQL_ATTR_MAX_BUFFER_SIZE') && \defined('PDO::MYSQL_ATTR_READ_DEFAULT_FILE') && \defined('PDO::MYSQL_ATTR_READ_DEFAULT_GROUP')) {
            $this->assertSame(1003, \Pdo\Mysql::ATTR_READ_DEFAULT_FILE);
            $this->assertSame(1004, \Pdo\Mysql::ATTR_READ_DEFAULT_GROUP);
            $this->assertSame(1005, \Pdo\Mysql::ATTR_MAX_BUFFER_SIZE);
        }
        if (\defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            $this->assertSame(\PHP_VERSION_ID < 80500 ? 1014 : 1013, \Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT);
        }
    }

    /**
     * @requires extension pdo_odbc
     */
    public function testOdbcConstants()
    {
        $this->assertSame(0, \Pdo\Odbc::SQL_USE_IF_NEEDED);
        $this->assertSame(1, \Pdo\Odbc::SQL_USE_ODBC);
        $this->assertSame(2, \Pdo\Odbc::SQL_USE_DRIVER);
        $this->assertSame(1000, \Pdo\Odbc::ATTR_USE_CURSOR_LIBRARY);
        $this->assertSame(1001, \Pdo\Odbc::ATTR_ASSUME_UTF8);
    }

    /**
     * @requires extension pdo_pgsql
     */
    public function testPgsqlConstants()
    {
        $this->assertSame(1000, \Pdo\Pgsql::ATTR_DISABLE_PREPARES);
    }

    /**
     * @requires extension pdo_sqlite
     */
    public function testSqliteConstants()
    {
        $this->assertSame(1, \Pdo\Sqlite::OPEN_READONLY);
        $this->assertSame(2, \Pdo\Sqlite::OPEN_READWRITE);
        $this->assertSame(4, \Pdo\Sqlite::OPEN_CREATE);
        $this->assertSame(1000, \Pdo\Sqlite::ATTR_OPEN_FLAGS);
        $this->assertSame(1001, \Pdo\Sqlite::ATTR_READONLY_STATEMENT);
        $this->assertSame(1002, \Pdo\Sqlite::ATTR_EXTENDED_RESULT_CODES);
        $this->assertSame(2048, \Pdo\Sqlite::DETERMINISTIC);
    }
}

if (class_exists('\Pdo\Sqlite')) {
    class ExtendedPdoSqlite extends \Pdo\Sqlite
    {
    }
}
