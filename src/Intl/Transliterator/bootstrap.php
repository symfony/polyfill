<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Polyfill\Intl\Transliterator as p;

if (!function_exists('transliterator_create')) {
    function transliterator_create($id, $direction = null) { return p\Transliterator::transliterator_create($id, $direction); }
}
