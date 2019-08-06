<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Php74 as p;

if (PHP_VERSION_ID < 70400) {
    if (!function_exists('get_mangled_object_vars')) {
        function get_mangled_object_vars($obj) { return p\Php74::get_mangled_object_vars($obj); }
    }

    if (!function_exists('mb_str_split') && function_exists('mb_substr')) {
        function mb_str_split($string, $split_length = 1, $encoding = null) { return p\Php74::mb_str_split($string, $split_length, $encoding); }
    }

    if (!function_exists('password_algos')) {
        function password_algos() { return p\Php74::password_algos(); }
    }
}
