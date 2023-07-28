<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Php83 as p;

if (\PHP_VERSION_ID >= 80300) {
    return;
}

if (!function_exists('json_validate')) {
    function json_validate(string $json, int $depth = 512, int $flags = 0): bool { return p\Php83::json_validate($json, $depth, $flags); }
}

if (!function_exists('mb_str_pad') && function_exists('mb_substr')) {
    function mb_str_pad(string $string, int $length, string $pad_string = ' ', int $pad_type = STR_PAD_RIGHT, ?string $encoding = null): string { return p\Php83::mb_str_pad($string, $length, $pad_string, $pad_type, $encoding); }
}
