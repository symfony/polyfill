<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Util;

if (2 /* MB_OVERLOAD_STRING */ & (int) ini_get('mbstring.func_overload')) {
    class Binary extends BinaryOnFuncOverload {}
} else {
    class Binary extends BinaryNoFuncOverload {}
}
