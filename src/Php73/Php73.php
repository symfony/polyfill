<?php

namespace Symfony\Polyfill\Php73;

/**
 * @author Gabriel Caruso <carusogabriel34@gmail.com>
 * @author Ion Bazan <ion.bazan@gmail.com>
 *
 * @internal
 */
final class Php73
{
    const NANO_IN_SEC = 1000000000;

    private static $startAt = null;

    /**
     * @param bool $asNum
     *
     * @return array|float|int
     */
    public static function hrtime($asNum = false)
    {
        if (null === self::$startAt) {
            self::$startAt = self::microtime();
        }

        $time = self::microtime();

        $secs = $time[1] - self::$startAt[1];
        $msecs = $time[0] - self::$startAt[0];
        if ($msecs < 0) {
            $msecs += 1;
            $secs -= 1;
        }

        if (!$asNum) {
            return array($secs, (int) ($msecs * self::NANO_IN_SEC));
        }

        if (\PHP_INT_SIZE === 4) {
            // Floor removes rounding errors from floating point diff
            return floor($msecs * self::NANO_IN_SEC) + ($secs * self::NANO_IN_SEC);
        }

        return (int) ($msecs * self::NANO_IN_SEC) + ($secs * self::NANO_IN_SEC);
    }

    private static function microtime()
    {
        $time = explode(' ', microtime());

        return array((float) $time[0], (int) $time[1]);
    }
}
