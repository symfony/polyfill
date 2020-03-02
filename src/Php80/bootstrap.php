<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Php80 as p;

if (PHP_VERSION_ID < 80000 && PHP_VERSION_ID >= 70000) {
    if (!function_exists('fdiv')) {
        function fdiv($dividend, $divisor) { return p\Php80::fdiv($dividend, $divisor); }
    }

    if (!function_exists('preg_last_error_msg')) {
        function preg_last_error_msg() { return p\Php80::preg_last_error_msg(); }
    }

    if (!defined('FILTER_VALIDATE_BOOL') && defined('FILTER_VALIDATE_BOOLEAN')) {
        define('FILTER_VALIDATE_BOOL', FILTER_VALIDATE_BOOLEAN);
    }

    if (!function_exists('str_contains')) {
        function str_contains() { return p\Php80::str_contains(); }
    }
}
