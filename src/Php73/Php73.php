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
    const NANO_IN_MSEC = 1e3;

    private static $startAt = null;
    private static $startAtArr = null;

    /**
     * @param bool $asNum
     *
     * @return array|float|int
     */
    public static function hrtime($asNum = false)
    {
        if ($asNum) {
            if (null === self::$startAt) {
                self::$startAt = microtime(true);
            }
            $nanos = (microtime(true) - self::$startAt) * self::NANO_IN_SEC;

            if (\PHP_INT_SIZE === 4) {
                return floor($nanos);
            }

            return (int) $nanos;
        }

        if (null === self::$startAtArr) {
            self::$startAtArr = explode(' ', microtime());
        }

        $time = explode(' ', microtime());

        return array((int) $time[1] - self::$startAt[1], (int) (($time[0] - self::$startAt[0]) * self::NANO_IN_MSEC));
    }
}
