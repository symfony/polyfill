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
    class Firebird
    {
        public const ATTR_DATE_FORMAT = PDO::FB_ATTR_DATE_FORMAT;
        public const ATTR_TIME_FORMAT = PDO::FB_ATTR_TIME_FORMAT;
        public const ATTR_TIMESTAMP_FORMAT = PDO::FB_ATTR_TIMESTAMP_FORMAT;
    }
}
