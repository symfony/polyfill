<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// this file should be kept in sync with src/Php80/bootstrap.php

use Symfony\Polyfill\Php80 as p;

if (PHP_VERSION_ID >= 80000) {
    return;
}

if (PHP_VERSION_ID >= 70000) {
    require __DIR__.'/Php80/bootstrap.php';

    return;
}

if (!defined('FILTER_VALIDATE_BOOL') && defined('FILTER_VALIDATE_BOOLEAN')) {
    define('FILTER_VALIDATE_BOOL', FILTER_VALIDATE_BOOLEAN);
}

if (!function_exists('fdiv')) {
    function fdiv($dividend, $divisor) { return p\Php80::fdiv($dividend, $divisor); }
}
if (!function_exists('preg_last_error_msg')) {
    function preg_last_error_msg() { return p\Php80::preg_last_error_msg(); }
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) { return p\Php80::str_contains($haystack, $needle); }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) { return p\Php80::str_starts_with($haystack, $needle); }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) { return p\Php80::str_ends_with($haystack, $needle); }
}
if (!function_exists('get_debug_type')) {
    function get_debug_type($value) { return p\Php80::get_debug_type($value); }
}
if (!function_exists('get_resource_id')) {
    function get_resource_id($res) { return p\Php80::get_resource_id($res); }
}
