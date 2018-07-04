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

    /**
     * @param bool $asNum
     *
     * @return array|float|int
     */
    public static function hrtime($asNum = false)
    {
        if ($asNum) {
            return microtime(true) * self::NANO_IN_SEC;
        }

        $time = explode(' ', microtime());

        return array((int) $time[1], (int) ($time[0] * self::NANO_IN_MSEC));
    }
}
