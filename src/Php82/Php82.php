<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Php82;

/**
 * @author Alexander M. Turek <me@derrabus.de>
 *
 * @internal
 */
class Php82
{
    /**
     * Determines if a string matches the ODBC quoting rules.
     *
     * A valid quoted string begins with a '{', ends with a '}', and has no '}'
     * inside of the string that aren't repeated (as to be escaped).
     *
     * These rules are what .NET also follows.
     *
     * @see https://github.com/php/php-src/blob/838f6bffff6363a204a2597cbfbaad1d7ee3f2b6/main/php_odbc_utils.c#L31-L57
     */
    public static function odbc_connection_string_is_quoted(string $str): bool
    {
        if ('' === $str || '{' !== $str[0]) {
            return false;
        }

        /* Check for } that aren't doubled up or at the end of the string */
        $length = \strlen($str) - 1;
        for ($i = 0; $i < $length; ++$i) {
            if ('}' !== $str[$i]) {
                continue;
            }

            if ('}' !== $str[++$i]) {
                return $i === $length;
            }
        }

        return true;
    }

    /**
     * Determines if a value for a connection string should be quoted.
     *
     * The ODBC specification mentions:
     * "Because of connection string and initialization file grammar, keywords and
     * attribute values that contain the characters []{}(),;?*=!@ not enclosed
     * with braces should be avoided."
     *
     * Note that it assumes that the string is *not* already quoted. You should
     * check beforehand.
     *
     * @see https://github.com/php/php-src/blob/838f6bffff6363a204a2597cbfbaad1d7ee3f2b6/main/php_odbc_utils.c#L59-L73
     */
    public static function odbc_connection_string_should_quote(string $str): bool
    {
        return false !== strpbrk($str, '[]{}(),;?*=!@');
    }

    public static function odbc_connection_string_quote(string $str): string
    {
        return '{'.str_replace('}', '}}', $str).'}';
    }
}
