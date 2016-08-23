<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Php70;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @internal
 */
class Php70Random
{
    private static $rngSrc;
    private static $rngHandle;

    public static function random_bytes($length)
    {
        $length = Php70::intArg($length, __FUNCTION__, 1);

        if (0 >= $length) {
            throw new \Error('Length must be greater than 0');
        }

        if (null === self::$rngSrc) {
            self::$rngSrc = '';
            switch (true) {
                case function_exists('mcrypt_create_iv') && (DIRECTORY_SEPARATOR !== '/' || PHP_VERSION_ID < 50610 || 50612 < PHP_VERSION_ID): self::$rngSrc = 'mcrypt'; break;
                case function_exists('Sodium\randombytes_buf'): self::$rngSrc = 'sodium02'; break;
                case method_exists('Sodium', 'randombytes_buf'): self::$rngSrc = 'sodium01'; break;
                default:
                    if ('\\' !== DIRECTORY_SEPARATOR && $h = @fopen('/dev/urandom', 'rb')) {
                        $stat = fstat($h);
                        if (020000 === ($stat['mode'] & 0170000)) {
                            if (function_exists('stream_set_read_buffer')) {
                                stream_set_read_buffer($h, 0);
                            }
                            self::$rngHandle = $h;
                            self::$rngSrc = 'urandom';
                        }
                    }
            }
        }

        switch (self::$rngSrc) {
            case 'mcrypt': $bytes = @mcrypt_create_iv($length, MCRYPT_DEV_URANDOM); break;
            case 'sodium02': $bytes = \Sodium\randombytes_buf($length); break;
            case 'sodium01': $bytes = \Sodium::randombytes_buf($length); break;
            case 'urandom': $bytes = fread(self::$rngHandle, $length); break;
            default:
                throw new \Exception('Cannot open source device');
        }

        if (!isset($bytes[$length - 1])) {
            throw new \Exception('Could not gather sufficient random data');
        }

        return $bytes;
    }

    public static function random_int($min, $max)
    {
        $min = Php70::intArg($min, __FUNCTION__, 1);
        $max = Php70::intArg($max, __FUNCTION__, 2);

        if ($max < $min) {
            throw new \Error('Minimum value must be less than or equal to the maximum value');
        }
        if ($min === $max) {
            return $min;
        }

        if (is_float($range = $max - $min)) {
            $bitmask = -1;
            $offset = 0;
        } else {
            $bitmask = ~(-1 << strlen(decbin($range)));
            $offset = $min;
        }
        $try = 128;

        do {
            $rand = self::random_bytes(PHP_INT_SIZE);
            $result = ord($rand[0]);
            for ($i = 1; $i < PHP_INT_SIZE; ++$i) {
                $result = ($result << 8) | ord($rand[$i]);
            }
            $result &= $bitmask;
            $result += $offset;
        } while (($result > $max || $min > $result) && $try--);

        if (!$try) {
            throw new \Exception('Could not gather sufficient random data');
        }

        return $result;
    }
}
