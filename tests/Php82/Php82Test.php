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
}
