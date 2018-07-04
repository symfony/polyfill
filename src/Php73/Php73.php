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
    const NANO_IN_SEC = 1e9;
    const NANO_IN_MSEC = 1000;
    const MSEC_IN_SEC = 1e6;

    private static $startAt = null;
    private static $startAtArr = null;

    /**
     * @param bool $asNum
     *
     * @return array|float|int
     */
    public static function hrtime($asNum = false)
    {
        if (null === self::$startAt) {
            self::$startAtArr = self::microtimeNumbers();
            self::$startAt = self::$startAtArr[0] + (float) self::$startAtArr[1];
            if (\PHP_INT_SIZE !== 4) {
                self::$startAt = (int) (self::$startAt * self::MSEC_IN_SEC);
            }
        }

        if ($asNum) {
            if (\PHP_INT_SIZE === 4) {
                $nanos = (microtime(true) - self::$startAt) * self::NANO_IN_SEC;

                // Floor removes rounding errors from floating point
                return floor($nanos);
            }
            $now = (int) (microtime(true) * self::MSEC_IN_SEC);

            return ($now - self::$startAt) * self::NANO_IN_MSEC;
        }

        $time = self::microtimeNumbers();

        $secs = $time[1] - self::$startAtArr[1];
        $msecs = $time[0] - self::$startAtArr[0];
        if ($msecs < 0) {
            $msecs += 1;
            $secs -= 1;
        }

        return array($secs, (int) ($msecs * self::NANO_IN_SEC));
    }

    private static function microtimeNumbers()
    {
        $time = explode(' ', microtime());

        return array((float) $time[0], (int) $time[1]);
    }
}
