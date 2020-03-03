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

if (PHP_VERSION_ID < 80000) {
    if (!function_exists('fdiv')) {
        function fdiv(float $dividend, float $divisor): float { return p\Php80::fdiv($dividend, $divisor); }
    }

    if (!function_exists('preg_last_error_msg')) {
        function preg_last_error_msg(): string { return p\Php80::preg_last_error_msg(); }
    }

    if (!function_exists('str_contains')) {
        function str_contains(string $haystack, string $needle): bool { return p\Php80::str_contains($haystack, $needle); }
    }

    if (!defined('FILTER_VALIDATE_BOOL') && defined('FILTER_VALIDATE_BOOLEAN')) {
        define('FILTER_VALIDATE_BOOL', FILTER_VALIDATE_BOOLEAN);
    }

    if (!function_exists('get_debug_type')) {
        function get_debug_type($value): string { return p\Php80::get_debug_type($value); }
    }
}
