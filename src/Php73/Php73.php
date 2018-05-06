<?php

namespace Symfony\Polyfill\Php73;

/**
 * @internal
 */
final class Php73
{
    const NANO_IN_SEC = 1000000000.0;

    public static function is_countable($var)
    {
        return is_array($var)
            || $var instanceof \Countable
            || $var instanceof \ResourceBundle
            || $var instanceof \SimpleXmlElement;
    }

    public static function hrtime($as_num = false)
    {
        if ($as_num) {
            return microtime(true) * self::NANO_IN_SEC;
        }

        $time = explode(' ', microtime());

        return array((int) $time[1], (int) ($time[0] * self::NANO_IN_SEC));
    }
}
