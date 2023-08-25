<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php82;

use PHPUnit\Framework\TestCase;

/**
 * @requires extension odbc
 */
class Php82Test extends TestCase
{
    /**
     * @dataProvider provideConnectionStringValuesFromUpstream
     * @dataProvider provideMoreConnectionStringValues
     */
    public function testConnectionStringIsQuoted(string $value, bool $isQuoted)
    {
        self::assertSame($isQuoted, odbc_connection_string_is_quoted($value));
    }

    /**
     * @dataProvider provideConnectionStringValuesFromUpstream
     * @dataProvider provideMoreConnectionStringValues
     */
    public function testConnectionStringShouldQuote(string $value, bool $isQuoted, bool $shouldQuote)
    {
        self::assertSame($shouldQuote, odbc_connection_string_should_quote($value));
    }

    /**
     * @dataProvider provideConnectionStringValuesFromUpstream
     * @dataProvider provideMoreConnectionStringValues
     */
    public function testConnectionStringQuote(string $value, bool $isQuoted, bool $shouldQuote, string $quoted)
    {
        self::assertSame($quoted, odbc_connection_string_quote($value));
    }

    /**
     * Test cases ported from upstream.
     *
     * @see https://github.com/php/php-src/blob/838f6bffff6363a204a2597cbfbaad1d7ee3f2b6/ext/odbc/tests/odbc_utils.phpt
     *
     * @return \Generator<string, array{string, bool, bool, string}>
     */
    public static function provideConnectionStringValuesFromUpstream(): \Generator
    {
        // 1. No, it's not quoted.
        // 2. Yes, it should be quoted because of the special character in the middle.
        yield 'with_end_curly1' => ['foo}bar', false, true, '{foo}}bar}'];

        // 1. No, the unescaped special character in the middle breaks what would be quoted.
        // 2. Yes, it should be quoted because of the special character in the middle.
        //    Note that should_quote doesn't care about if the string is already quoted.
        //    That's why you should check if it is quoted first.
        yield 'with_end_curly2' => ['{foo}bar}', false, true, '{{foo}}bar}}}'];

        // 1. Yes, the special characters are escaped, so it's quoted.
        // 2. See $with_end_curly2; should_quote doesn't care about if the string is already quoted.
        yield 'with_end_curly3' => ['{foo}}bar}', true, true, '{{foo}}}}bar}}}'];

        // 1. No, it's not quoted.
        // 2. It doesn't need to be quoted because of no s
        yield 'with_no_end_curly1' => ['foobar', false, false, '{foobar}'];

        // 1. Yes, it is quoted and any characters are properly escaped.
        // 2. See $with_end_curly2.
        yield 'with_no_end_curly2' => ['{foobar}', true, true, '{{foobar}}}'];
    }

    /**
     * @return \Generator<string, array{string, bool, bool, string}>
     */
    public static function provideMoreConnectionStringValues(): \Generator
    {
        yield 'double curly at the end' => ['foo}}', false, true, '{foo}}}}}'];
    }

    public function testIniParseQuantity()
    {
        $this->assertSame(0, ini_parse_quantity(''));
        $this->assertSame(0, ini_parse_quantity('0'));
        $this->assertSame(0, ini_parse_quantity(' 0'));
        $this->assertSame(0, ini_parse_quantity('0 '));
        $this->assertSame(0, ini_parse_quantity(' +0 '));
        $this->assertSame(0, ini_parse_quantity(' -0 '));

        $this->assertSame(0, ini_parse_quantity('00'));
        $this->assertSame(0, ini_parse_quantity(' 00'));
        $this->assertSame(0, ini_parse_quantity('00 '));
        $this->assertSame(0, ini_parse_quantity(' +00 '));
        $this->assertSame(0, ini_parse_quantity(' -00 '));

        $this->assertSame(1, ini_parse_quantity('1'));
        $this->assertSame(-1, ini_parse_quantity('-1'));
        $this->assertSame(1, ini_parse_quantity('+1'));

        $this->assertSame(0, ini_parse_quantity('0b0'));
        $this->assertSame(2, ini_parse_quantity('0b10'));
        $this->assertSame(-2, ini_parse_quantity('-0b10'));
        $this->assertSame(2, ini_parse_quantity('+0b10'));

        $this->assertSame(0, ini_parse_quantity('0B0'));
        $this->assertSame(2, ini_parse_quantity('0B10'));
        $this->assertSame(-2, ini_parse_quantity('-0B10'));
        $this->assertSame(2, ini_parse_quantity('+0B10'));

        $this->assertSame(8, ini_parse_quantity('0o10'));
        $this->assertSame(-8, ini_parse_quantity('-0o10'));
        $this->assertSame(8, ini_parse_quantity('+0o10'));

        $this->assertSame(8, ini_parse_quantity('0O10'));
        $this->assertSame(-8, ini_parse_quantity('-0O10'));
        $this->assertSame(8, ini_parse_quantity('+0O10'));

        $this->assertSame(16, ini_parse_quantity('0x10'));
        $this->assertSame(-16, ini_parse_quantity('-0x10'));
        $this->assertSame(16, ini_parse_quantity('+0x10'));

        $this->assertSame(16, ini_parse_quantity('0X10'));
        $this->assertSame(-16, ini_parse_quantity('-0X10'));
        $this->assertSame(16, ini_parse_quantity('+0X10'));

        $this->assertSame(0, ini_parse_quantity('0k'));
        $this->assertSame(0, ini_parse_quantity(' 0K'));
        $this->assertSame(0, ini_parse_quantity('0k '));

        $this->assertSame(1024, ini_parse_quantity('1k'));
        $this->assertSame(1024, ini_parse_quantity('1 K'));
        $this->assertSame(1024, ini_parse_quantity('+1k'));
        $this->assertSame(-1024, ini_parse_quantity('-1K'));

        $this->assertSame(2048, ini_parse_quantity('0b10k'));
        $this->assertSame(2048, ini_parse_quantity('0b10 K'));
        $this->assertSame(2048, ini_parse_quantity('+0b10k'));
        $this->assertSame(-2048, ini_parse_quantity('-0b10K'));

        $this->assertSame(2048, ini_parse_quantity('0B10k'));
        $this->assertSame(2048, ini_parse_quantity('0B10 K'));
        $this->assertSame(2048, ini_parse_quantity('+0B10k'));
        $this->assertSame(-2048, ini_parse_quantity('-0B10K'));

        $this->assertSame(8192, ini_parse_quantity('0o10k'));
        $this->assertSame(8192, ini_parse_quantity('0o10 K'));
        $this->assertSame(8192, ini_parse_quantity('+0o10k'));
        $this->assertSame(-8192, ini_parse_quantity('-0o10K'));

        $this->assertSame(8192, ini_parse_quantity('0O10k'));
        $this->assertSame(8192, ini_parse_quantity('0O10 K'));
        $this->assertSame(8192, ini_parse_quantity('+0O10k'));
        $this->assertSame(-8192, ini_parse_quantity('-0O10K'));

        $this->assertSame(16384, ini_parse_quantity('0x10k'));
        $this->assertSame(16384, ini_parse_quantity('0x10 K'));
        $this->assertSame(16384, ini_parse_quantity('+0x10k'));
        $this->assertSame(-16384, ini_parse_quantity('-0x10K'));

        $this->assertSame(16384, ini_parse_quantity('0X10k'));
        $this->assertSame(16384, ini_parse_quantity('0X10 K'));
        $this->assertSame(16384, ini_parse_quantity('+0X10k'));
        $this->assertSame(-16384, ini_parse_quantity('-0X10K'));

        $this->assertSame(1048576, ini_parse_quantity('1m'));
        $this->assertSame(1048576, ini_parse_quantity('1 M'));
        $this->assertSame(1048576, ini_parse_quantity('+1m'));
        $this->assertSame(-1048576, ini_parse_quantity('-1M'));

        $this->assertSame(2097152, ini_parse_quantity('0b10m'));
        $this->assertSame(2097152, ini_parse_quantity('0b10 M'));
        $this->assertSame(2097152, ini_parse_quantity('+0b10m'));
        $this->assertSame(-2097152, ini_parse_quantity('-0b10M'));

        $this->assertSame(2097152, ini_parse_quantity('0B10m'));
        $this->assertSame(2097152, ini_parse_quantity('0B10 M'));
        $this->assertSame(2097152, ini_parse_quantity('+0B10m'));
        $this->assertSame(-2097152, ini_parse_quantity('-0B10M'));

        $this->assertSame(8388608, ini_parse_quantity('0o10m'));
        $this->assertSame(8388608, ini_parse_quantity('0o10 M'));
        $this->assertSame(8388608, ini_parse_quantity('+0o10m'));
        $this->assertSame(-8388608, ini_parse_quantity('-0o10M'));

        $this->assertSame(8388608, ini_parse_quantity('0O10m'));
        $this->assertSame(8388608, ini_parse_quantity('0O10 M'));
        $this->assertSame(8388608, ini_parse_quantity('+0O10m'));
        $this->assertSame(-8388608, ini_parse_quantity('-0O10M'));

        $this->assertSame(16777216, ini_parse_quantity('0x10m'));
        $this->assertSame(16777216, ini_parse_quantity('0x10 M'));
        $this->assertSame(16777216, ini_parse_quantity('+0x10m'));
        $this->assertSame(-16777216, ini_parse_quantity('-0x10M'));

        $this->assertSame(16777216, ini_parse_quantity('0X10m'));
        $this->assertSame(16777216, ini_parse_quantity('0X10 M'));
        $this->assertSame(16777216, ini_parse_quantity('+0X10m'));
        $this->assertSame(-16777216, ini_parse_quantity('-0X10M'));

        $this->assertSame(1073741824, ini_parse_quantity('1g'));
        $this->assertSame(1073741824, ini_parse_quantity('1 G'));
        $this->assertSame(1073741824, ini_parse_quantity('+1g'));
        $this->assertSame(-1073741824, ini_parse_quantity('-1G'));

        $this->assertSame(2147483648, ini_parse_quantity('0b10g'));
        $this->assertSame(2147483648, ini_parse_quantity('0b10 G'));
        $this->assertSame(2147483648, ini_parse_quantity('+0b10g'));
        $this->assertSame(-2147483648, ini_parse_quantity('-0b10G'));

        $this->assertSame(2147483648, ini_parse_quantity('0B10g'));
        $this->assertSame(2147483648, ini_parse_quantity('0B10 G'));
        $this->assertSame(2147483648, ini_parse_quantity('+0B10g'));
        $this->assertSame(-2147483648, ini_parse_quantity('-0B10G'));

        $this->assertSame(8589934592, ini_parse_quantity('0o10g'));
        $this->assertSame(8589934592, ini_parse_quantity('0o10 G'));
        $this->assertSame(8589934592, ini_parse_quantity('+0o10g'));
        $this->assertSame(-8589934592, ini_parse_quantity('-0o10G'));

        $this->assertSame(8589934592, ini_parse_quantity('0O10g'));
        $this->assertSame(8589934592, ini_parse_quantity('0O10 G'));
        $this->assertSame(8589934592, ini_parse_quantity('+0O10g'));
        $this->assertSame(-8589934592, ini_parse_quantity('-0O10G'));

        $this->assertSame(17179869184, ini_parse_quantity('0x10g'));
        $this->assertSame(17179869184, ini_parse_quantity('0x10 G'));
        $this->assertSame(17179869184, ini_parse_quantity('+0x10g'));
        $this->assertSame(-17179869184, ini_parse_quantity('-0x10G'));

        $this->assertSame(17179869184, ini_parse_quantity('0X10g'));
        $this->assertSame(17179869184, ini_parse_quantity('0X10 G'));
        $this->assertSame(17179869184, ini_parse_quantity('+0X10g'));
        $this->assertSame(-17179869184, ini_parse_quantity('-0X10G'));
    }

    public function testIniParseQuantityUndocumentedFeatures()
    {
        $this->assertSame(18, ini_parse_quantity('0x0x12'));

        $this->assertSame(2, ini_parse_quantity('0b+10'));
        $this->assertSame(8, ini_parse_quantity('0o+10'));
        $this->assertSame(16, ini_parse_quantity('0x+10'));

        $this->assertSame(2, ini_parse_quantity('0b 10'));
        $this->assertSame(8, ini_parse_quantity('0o 10'));
        $this->assertSame(16, ini_parse_quantity('0x 10'));
    }

    public function testIniParseQuantityZeroWithMultiplier()
    {
        // Note that "1 K" is valid
        error_clear_last();
        $this->assertSame(0, @ini_parse_quantity(' 0 K '));
        $this->assertSame('Invalid prefix "0 ", interpreting as "0" for backwards compatibility', error_get_last()['message']);
        $this->assertContains(error_get_last()['type'], [E_WARNING, E_USER_WARNING]);
    }

    public function testIniParseQuantityNoValidLeadingDigits()
    {
        error_clear_last();
        $this->assertSame(0, @ini_parse_quantity(' foo '));
        $this->assertSame('Invalid quantity " foo ": no valid leading digits, interpreting as "0" for backwards compatibility', error_get_last()['message']);
        $this->assertContains(error_get_last()['type'], [E_WARNING, E_USER_WARNING]);
    }

    public function testIniParseQuantityUnknownMultiplier()
    {
        error_clear_last();
        $this->assertSame(2, @ini_parse_quantity(' 0b102 '));
        $this->assertSame('Invalid quantity " 0b102 ": unknown multiplier "2", interpreting as " 0b10" for backwards compatibility', error_get_last()['message']);
        $this->assertContains(error_get_last()['type'], [E_WARNING, E_USER_WARNING]);
    }

    public function testIniParseQuantityNoDigitsAfterBasePrefix()
    {
        error_clear_last();
        $this->assertSame(0, @ini_parse_quantity(' 0x '));
        $this->assertSame('Invalid quantity " 0x ": no digits after base prefix, interpreting as "0" for backwards compatibility', error_get_last()['message']);
        $this->assertContains(error_get_last()['type'], [E_WARNING, E_USER_WARNING]);
    }

    public function testIniParseQuantityInvalidPrefix()
    {
        error_clear_last();
        $this->assertSame(0, @ini_parse_quantity('0q12'));
        $this->assertSame('Invalid prefix "0q", interpreting as "0" for backwards compatibility', error_get_last()['message']);
        $this->assertContains(error_get_last()['type'], [E_WARNING, E_USER_WARNING]);
    }

    public function testIniParseQuantityInvalidQuantity()
    {
        error_clear_last();
        $this->assertSame(10240, @ini_parse_quantity(' 10 kk '));
        $this->assertSame('Invalid quantity " 10 kk ", interpreting as " 10 k" for backwards compatibility', error_get_last()['message']);
        $this->assertContains(error_get_last()['type'], [E_WARNING, E_USER_WARNING]);
    }

    public function testIniParseQuantityNoLeadingDigits()
    {
        // There are two paths to generate this error
        error_clear_last();
        $this->assertSame(0, @ini_parse_quantity(' K '));
        $this->assertSame('Invalid quantity " K ": no valid leading digits, interpreting as "0" for backwards compatibility', error_get_last()['message']);
        $this->assertContains(error_get_last()['type'], [E_WARNING, E_USER_WARNING]);
    }

    public function testIniParseQuantityOutOfRange()
    {
        error_clear_last();
        $this->assertSame(-4096, @ini_parse_quantity(' 0x-4K '));
        $this->assertSame('Invalid quantity " 0x-4K ": value is out of range, using overflow result for backwards compatibility', error_get_last()['message']);
        $this->assertContains(error_get_last()['type'], [E_WARNING, E_USER_WARNING]);
    }

    public function testIniParseQuantityOverflowTooManyDigits()
    {
        error_clear_last();
        $this->assertSame(-1, @ini_parse_quantity(' 99999999999999999999 '));
        $this->assertSame('Invalid quantity " 99999999999999999999 ": value is out of range, using overflow result for backwards compatibility', error_get_last()['message']);
        $this->assertContains(error_get_last()['type'], [E_WARNING, E_USER_WARNING]);
    }

    public function testIniParseQuantityOverflowWithMultiplier()
    {
        error_clear_last();
        $this->assertSame(-7709325833709551616, @ini_parse_quantity(' 10000000000G '));
        $this->assertSame('Invalid quantity " 10000000000G ": value is out of range, using overflow result for backwards compatibility', error_get_last()['message']);
        $this->assertContains(error_get_last()['type'], [E_WARNING, E_USER_WARNING]);
    }

    public function testIniParseQuantitySignAfterPrefixButNoDigits()
    {
        error_clear_last();
        $this->assertSame(0, @ini_parse_quantity(' 0b- '));
        $this->assertSame('Invalid quantity " 0b- ": no valid leading digits, interpreting as "0" for backwards compatibility', error_get_last()['message']);
        $this->assertContains(error_get_last()['type'], [E_WARNING, E_USER_WARNING]);
    }

    public function testIniParseQuantitySpecialCharactersAreEscaped()
    {
        error_clear_last();
        $this->assertSame(0, @ini_parse_quantity("w-\n-\r-\t-\v-\f-\\-\x1B-\xCC-"));
        $this->assertSame('Invalid quantity "w-\\n-\\r-\\t-\\v-\\f-\\\\-\\e-\\xCC-": no valid leading digits, interpreting as "0" for backwards compatibility', error_get_last()['message']);
        $this->assertContains(error_get_last()['type'], [E_WARNING, E_USER_WARNING]);
    }

    /**
     * This is a brute-force test that should only be used during local development.
     * It takes approximately 4 hours to run.  It is good at picking up edge cases.
     */
    public function testIniParseQuantityUsingBruteForce()
    {
        if (PHP_VERSION_ID < 80200) {
            $this->markTestSkipped('This test requires the PHP function as a reference.');

            return;
        }

        // Comment these lines to run the tests.
        $this->markTestSkipped('This test is slow and should only be used for local development.');
        return;

        $fn = function (string $test): void {
            error_clear_last();
            $x     = @ini_parse_quantity($test);
            $err_x = error_get_last()['message'] ?? '';

            error_clear_last();
            $y     = @ini_parse_quantity($test);
            $err_y = error_get_last()['message'] ?? '';

            $this->assertSame($x, $y, 'Testing "' . $test . '"');
            $this->assertSame($err_x, $err_y, 'Testing "' . $test . '"');
        };

        $chars = [' ', '-', '+', '0', '1', '7', '9', 'a', 'b', 'o', 'f', 'g', 'k', 'm', 'x', 'z', '\\'];

        $fn('');

        foreach ($chars as $char1) {
            $fn($char1);

            foreach ($chars as $char2) {
                $fn($char1 . $char2);

                foreach ($chars as $char3) {
                    $fn($char1 . $char2 . $char3);

                    foreach ($chars as $char4) {
                        $fn($char1 . $char2 . $char3 . $char4);

                        foreach ($chars as $char5) {
                            $fn($char1 . $char2 . $char3 . $char4 . $char5);

                            foreach ($chars as $char6) {
                                $fn($char1 . $char2 . $char3 . $char4 . $char5 . $char6);

                                foreach ($chars as $char7) {
                                    $fn($char1 . $char2 . $char3 . $char4 . $char5 . $char6 . $char7);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
