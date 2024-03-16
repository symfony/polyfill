<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Php84;

/**
 * @author Ayesh Karunaratne <ayesh@aye.sh>
 *
 * @internal
 */
final class Php84
{
    public static function mb_ucfirst(string $string, ?string $encoding = null): string {
        if (null === $encoding) {
            $encoding = mb_internal_encoding();
        }

        try {
            $validEncoding = @mb_check_encoding('', $encoding);
        } catch (\ValueError $e) {
            throw new \ValueError(sprintf('mb_ucfirst(): Argument #2 ($encoding) must be a valid encoding, "%s" given', $encoding));
        }

        // BC for PHP 7.3 and lower
        if (!$validEncoding) {
            throw new \ValueError(sprintf('mb_ucfirst(): Argument #2 ($encoding) must be a valid encoding, "%s" given', $encoding));
        }

        $firstChar = mb_substr($string, 0, 1, $encoding);
        $firstChar = mb_convert_case($firstChar, MB_CASE_TITLE, $encoding);

        return $firstChar . mb_substr($string, 1, null, $encoding);
    }

    public static function mb_lcfirst(string $string, ?string $encoding = null): string {
        if (null === $encoding) {
            $encoding = mb_internal_encoding();
        }

        try {
            $validEncoding = @mb_check_encoding('', $encoding);
        } catch (\ValueError $e) {
            throw new \ValueError(sprintf('mb_lcfirst(): Argument #2 ($encoding) must be a valid encoding, "%s" given', $encoding));
        }

        // BC for PHP 7.3 and lower
        if (!$validEncoding) {
            throw new \ValueError(sprintf('mb_lcfirst(): Argument #2 ($encoding) must be a valid encoding, "%s" given', $encoding));
        }

        $firstChar = mb_substr($string, 0, 1, $encoding);
        $firstChar = mb_convert_case($firstChar, MB_CASE_LOWER, $encoding);

        return $firstChar . mb_substr($string, 1, null, $encoding);
    }
}
