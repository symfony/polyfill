<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Php82 as p;

if (\PHP_VERSION_ID >= 80200) {
    return;
}

if (!function_exists('ini_parse_quantity')) {
    function ini_parse_quantity(string $shorthand): int { return p\Php82::ini_parse_quantity($shorthand); }
}
