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
    class Pgsql
    {
        public const ATTR_DISABLE_PREPARES = PDO::PGSQL_ATTR_DISABLE_PREPARES;
    }
}
