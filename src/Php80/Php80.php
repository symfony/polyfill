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
 * @author Nico Oelgart <nicoswd@gmail.com>
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

    public static function pregLastErrorMsg()
    {
        switch (preg_last_error()) {
            case PREG_INTERNAL_ERROR:
                return 'Internal error';
            case PREG_BAD_UTF8_ERROR:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            case PREG_BAD_UTF8_OFFSET_ERROR:
                return 'The offset did not correspond to the beginning of a valid UTF-8 code point';
            case PREG_BACKTRACK_LIMIT_ERROR:
                return 'Backtrack limit exhausted';
            case PREG_RECURSION_LIMIT_ERROR:
                return 'Recursion limit exhausted';
            case PREG_JIT_STACKLIMIT_ERROR:
                return 'JIT stack limit exhausted';
            case PREG_NO_ERROR:
                return 'No error';
            default:
                return 'Unknown error';
        }
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
