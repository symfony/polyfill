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
    const NANO_IN_SEC = 1000000000.0;

    /**
     * @param mixed $var
     *
     * @return bool
     */
    public static function is_countable($var)
    {
        return is_array($var)
            || $var instanceof \Countable
            || $var instanceof \ResourceBundle
            || $var instanceof \SimpleXmlElement;
    }

    /**
     * @param bool $as_num
     *
     * @return array|float
     */
    public static function hrtime($as_num = false)
    {
        if ($as_num) {
            return microtime(true) * self::NANO_IN_SEC;
        }

        $time = explode(' ', microtime());

        return array((int) $time[1], (int) ($time[0] * self::NANO_IN_SEC));
    }
}
