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
    const NANO_IN_MSEC = 1000;
    const MSEC_IN_SEC = 1000000.0;

    private static $startAt = null;

    /**
     * @param bool $asNum
     *
     * @return array|float|int
     */
    public static function hrtime($asNum = false)
    {
        if (null === self::$startAt) {
            self::$startAt = microtime(true);
            if (\PHP_INT_SIZE !== 4) {
                // In this case $startAt is a int, number of micro seconds
                self::$startAt = (int) (self::$startAt * self::MSEC_IN_SEC);
            }
        }

        if (\PHP_INT_SIZE === 4) {
            // Floor removes rounding errors from floating point
            $nanos = floor((microtime(true) - self::$startAt) * self::NANO_IN_SEC);
        } else {
            $nowNanos = (int)(microtime(true) * self::MSEC_IN_SEC);

            $nanos = ($nowNanos - self::$startAt) * self::NANO_IN_MSEC;
        }

        if ($asNum) {
            return $nanos;
        }

        $secs = (int) ($nanos / self::NANO_IN_SEC);
        $nanosPart = (int) $nanos - ($secs * self::NANO_IN_SEC);

        return array($secs, $nanosPart);
    }
}
