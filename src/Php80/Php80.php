<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Php80;

/**
 * @author Ion Bazan <ion.bazan@gmail.com>
 *
 * @internal
 */
final class Php80
{
    public static function fdiv($dividend, $divisor)
    {
        $dividend = self::floatArg($dividend, __FUNCTION__, 1);
        $divisor = self::floatArg($divisor, __FUNCTION__, 2);

        return (float) @($dividend / $divisor);
    }

    private static function floatArg($value, $caller, $pos)
    {
        if (\is_float($value)) {
            return $value;
        }

        if (!\is_numeric($value)) {
            throw new \TypeError(sprintf('%s() expects parameter %d to be float, %s given', $caller, $pos, \gettype($value)));
        }

        return (float) $value;
    }
}
