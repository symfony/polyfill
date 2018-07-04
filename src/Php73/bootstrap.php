<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Php73 as p;

if (PHP_VERSION_ID < 70300) {
    if (!function_exists('is_countable')) {
        function is_countable($var) { return p\Php73::is_countable($var); }
    }

    if (!function_exists('hrtime')) {
        function hrtime($asNum = false) { return p\Php73::hrtime($asNum); }
    }
}
