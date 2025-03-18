<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php84;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class Php84Test extends TestCase
{
    /**
     * @dataProvider ucFirstDataProvider
     */
    public function testMbUcFirst(string $string, string $expected)
    {
        $this->assertSame($expected, mb_ucfirst($string));
    }

    /**
     * @dataProvider lcFirstDataProvider
     */
    public function testMbLcFirst(string $string, string $expected)
    {
        $this->assertSame($expected, mb_lcfirst($string));
    }

    /**
     * @dataProvider arrayFindDataProvider
     */
    public function testArrayFind(array $array, callable $callback, $expected)
    {
        $this->assertSame($expected, array_find($array, $callback));
    }

    /**
     * @dataProvider arrayFindKeyDataProvider
     */
    public function testArrayFindKey(array $array, callable $callback, $expected)
    {
        $this->assertSame($expected, array_find_key($array, $callback));
    }

    /**
     * @dataProvider arrayAnyDataProvider
     */
    public function testArrayAny(array $array, callable $callback, bool $expected)
    {
        $this->assertSame($expected, array_any($array, $callback));
    }

    /**
     * @dataProvider arrayAllDataProvider
     */
    public function testArrayAll(array $array, callable $callback, bool $expected)
    {
        $this->assertSame($expected, array_all($array, $callback));
    }

    /**
     * @requires extension curl
     */
    public function testCurlHttp3Constants()
    {
        $ch = curl_init();

        $this->assertIsBool(curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_3));

        if (defined('CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256')) {
            $this->assertIsBool(curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_3ONLY));
        }
    }

    public static function ucFirstDataProvider(): array
    {
        return [
            ['', ''],
            ['test', 'Test'],
            ['TEST', 'TEST'],
            ['TesT', 'TesT'],
            ['ａｂ', 'Ａｂ'],
            ['ＡＢＳ', 'ＡＢＳ'],
            ['đắt quá!', 'Đắt quá!'],
            ['აბგ', 'აბგ'],
            ['ǉ', 'ǈ'],
            ["\u{01CA}", "\u{01CB}"],
            ["\u{01CA}\u{01CA}", "\u{01CB}\u{01CA}"],
            ['łámał', 'Łámał'],
            // Full case-mapping and case-folding that changes the length of the string only supported
            // in PHP > 7.3.
            ['ßst', \PHP_VERSION_ID < 70300 ? 'ßst' : 'Ssst'],
        ];
    }

    public static function lcFirstDataProvider(): array
    {
        return [
            ['', ''],
            ['test', 'test'],
            ['Test', 'test'],
            ['tEST', 'tEST'],
            ['Ａｂ', 'ａｂ'],
            ['ＡＢＳ', 'ａＢＳ'],
            ['Đắt quá!', 'đắt quá!'],
            ['აბგ', 'აბგ'],
            ['ǈ', \PHP_VERSION_ID < 70200 ? 'ǈ' : 'ǉ'],
            ["\u{01CB}", \PHP_VERSION_ID < 70200 ? "\u{01CB}" : "\u{01CC}"],
            ["\u{01CA}", "\u{01CC}"],
            ["\u{01CA}\u{01CA}", "\u{01CC}\u{01CA}"],
            ["\u{212A}\u{01CA}", "\u{006b}\u{01CA}"],
            ['ß', 'ß'],
        ];
    }

    public static function arrayFindDataProvider(): array
    {
        $callable = function ($value): bool {
            return \strlen($value) > 2;
        };

        $callableKey = function ($value, $key): bool {
            return is_numeric($key);
        };

        return [
            [[], $callable, null],
            [['a', 'aa', 'aaa', 'aaaa'], $callable, 'aaa'],
            [['a', 'aa'], $callable, null],
            [['a' => '1', 'b' => '12', 'c' => '123', 'd' => '1234'], $callable, '123'],
            [['a' => '1', 'b' => '12', 'c' => '123', 3 => '1234'], $callableKey, '1234'],
        ];
    }

    public static function arrayFindKeyDataProvider(): array
    {
        $callable = function ($value): bool {
            return \strlen($value) > 2;
        };

        $callableKey = function ($value, $key): bool {
            return is_numeric($key);
        };

        return [
            [[], $callable, null],
            [['a', 'aa', 'aaa', 'aaaa'], $callable, 2],
            [['a', 'aa'], $callable, null],
            [['a' => '1', 'b' => '12', 'c' => '123', 'd' => '1234'], $callable, 'c'],
            [['a' => '1', 'b' => '12', 'c' => '123', 3 => '1234'], $callableKey, 3],
        ];
    }

    public static function arrayAnyDataProvider(): array
    {
        $callable = function ($value): bool {
            return \strlen($value) > 2;
        };

        $callableKey = function ($value, $key): bool {
            return is_numeric($key);
        };

        return [
            [[], $callable, false],
            [['a', 'aa', 'aaa', 'aaaa'], $callable, true],
            [['a', 'aa'], $callable, false],
            [['a' => '1', 'b' => '12', 'c' => '123', 'd' => '1234'], $callable, true],
            [['a' => '1', 'b' => '12', 'c' => '123', 3 => '1234'], $callableKey, true],
            [['a' => '1', 'b' => '12', 'c' => '123', 'd' => '1234'], $callableKey, false],
        ];
    }

    public static function arrayAllDataProvider(): array
    {
        $callable = function ($value): bool {
            return \strlen($value) > 2;
        };

        $callableKey = function ($value, $key): bool {
            return is_numeric($key);
        };

        return [
            [[], $callable, true],
            [['a', 'aa', 'aaa', 'aaaa'], $callable, false],
            [['aaa', 'aaa'], $callable, true],
            [['a' => '1', 'b' => '12', 'c' => '123', 'd' => '1234'], $callable, false],
            [['a' => '1', 'b' => '12', 'c' => '123', 'd' => '1234'], $callableKey, false],
            [[1 => '1', 2 => '12', 3 => '123', 4 => '1234'], $callableKey, true],
        ];
    }

    /**
     * @covers \Symfony\Polyfill\Php84\Php84::mb_trim
     *
     * @dataProvider mbTrimProvider
     */
    public function testMbTrim(string $expected, string $string, ?string $characters = null, ?string $encoding = null)
    {
        $this->assertSame($expected, mb_trim($string, $characters, $encoding));
    }

    /**
     * @covers \Symfony\Polyfill\Php84\Php84::mb_ltrim
     *
     * @dataProvider mbLTrimProvider
     */
    public function testMbLTrim(string $expected, string $string, ?string $characters = null, ?string $encoding = null)
    {
        $this->assertSame($expected, mb_ltrim($string, $characters, $encoding));
    }

    /**
     * @covers \Symfony\Polyfill\Php84\Php84::mb_rtrim
     *
     * @dataProvider mbRTrimProvider
     */
    public function testMbRTrim(string $expected, string $string, ?string $characters = null, ?string $encoding = null)
    {
        $this->assertSame($expected, mb_rtrim($string, $characters, $encoding));
    }

    public function testMbTrimException()
    {
        $this->expectException(\ValueError::class);
        mb_trim("\u{180F}", '', 'NULL');
    }

    public function testMbTrimEncoding()
    {
        $this->assertSame('あ', mb_convert_encoding(mb_trim("\x81\x40\x82\xa0\x81\x40", "\x81\x40", 'SJIS'), 'UTF-8', 'SJIS'));
        $this->assertSame('226f575b', bin2hex(mb_ltrim(mb_convert_encoding("\u{FFFE}漢字", 'UTF-16LE', 'UTF-8'), mb_convert_encoding("\u{FFFE}\u{FEFF}", 'UTF-16LE', 'UTF-8'), 'UTF-16LE')));
        $this->assertSame('6f225b57', bin2hex(mb_ltrim(mb_convert_encoding("\u{FEFF}漢字", 'UTF-16BE', 'UTF-8'), mb_convert_encoding("\u{FFFE}\u{FEFF}", 'UTF-16BE', 'UTF-8'), 'UTF-16BE')));
    }

    public function testMbTrimCharactersEncoding()
    {
        $strUtf8 = "\u{3042}\u{3000}";

        $this->assertSame(1, mb_strlen(mb_trim($strUtf8)));
        $this->assertSame(1, mb_strlen(mb_trim($strUtf8, null, 'UTF-8')));

        $old = mb_internal_encoding();
        mb_internal_encoding('Shift_JIS');
        $strSjis = mb_convert_encoding($strUtf8, 'Shift_JIS', 'UTF-8');

        $this->assertSame(1, mb_strlen(mb_trim($strSjis)));
        $this->assertSame(1, mb_strlen(mb_trim($strSjis, null, 'Shift_JIS')));
        mb_internal_encoding($old);
    }

    public static function mbTrimProvider(): iterable
    {
        yield ['ABC', 'ABC'];
        yield ['ABC', "\0\t\nABC \0\t\n"];
        yield ["\0\t\nABC \0\t\n", "\0\t\nABC \0\t\n", ''];

        yield ['', ''];

        yield ['あいうえおあお', ' あいうえおあお ', ' ', 'UTF-8'];
        yield ['foo BAR Spa', 'foo BAR Spaß', 'ß', 'UTF-8'];
        yield ['oo BAR Spaß', 'oo BAR Spaß', 'f', 'UTF-8'];

        yield ['oo BAR Spa', 'foo BAR Spaß', 'ßf', 'UTF-8'];
        yield ['oo BAR Spa', 'foo BAR Spaß', 'fß', 'UTF-8'];
        yield ['いうおえお', ' あいうおえお  あ', ' あ', 'UTF-8'];
        yield ['いうおえお', ' あいうおえお  あ', 'あ ', 'UTF-8'];
        yield [' あいうおえお ', ' あいうおえお a', 'あa', 'UTF-8'];
        yield [' あいうおえお  a', ' あいうおえお  a', "\xe3", 'UTF-8'];

        yield ['', str_repeat(' ', 129)];
        yield ['a', str_repeat(' ', 129).'a'];

        yield ['', " \f\n\r\v\x00\u{00A0}\u{1680}\u{2000}\u{2001}\u{2002}\u{2003}\u{2004}\u{2005}\u{2006}\u{2007}\u{2008}\u{2009}\u{200A}\u{2028}\u{2029}\u{202F}\u{205F}\u{3000}\u{0085}\u{180E}"];

        yield [' abcd ', ' abcd ', ''];

        yield ['f', 'foo', 'oo'];

        yield ["foo\n", "foo\n", 'o'];
    }

    public static function mbLTrimProvider(): iterable
    {
        yield ['ABC', 'ABC'];
        yield ["ABC \0\t\n", "\0\t\nABC \0\t\n"];
        yield ["\0\t\nABC \0\t\n", "\0\t\nABC \0\t\n", ''];

        yield ['', ''];

        yield [' test ', ' test ', ''];

        yield ['いああああ', 'あああああああああああああああああああああああああああああああああいああああ', 'あ'];

        yield ['漢字', "\u{FFFE}漢字", "\u{FFFE}\u{FEFF}"];
        yield [' abcd ', ' abcd ', ''];
    }

    public static function mbRTrimProvider(): iterable
    {
        yield ['ABC', 'ABC'];
        yield ['ABC', "ABC \0\t\n"];
        yield ["\0\t\nABC \0\t\n", "\0\t\nABC \0\t\n", ''];

        yield ['', ''];

        yield ['                                                                                                                                 a', str_repeat(' ', 129).'a'];

        yield ['あああああああああああああああああああああああああああああああああい', 'あああああああああああああああああああああああああああああああああいああああ', 'あ'];

        yield [' abcd ', ' abcd ', ''];

        yield ["foo\n", "foo\n", 'o'];
    }

    /**
     * @dataProvider fpowProvider
     */
    public function testFpow(float $num, float $exponent, float $expected)
    {
        $result = fpow($num, $exponent);

        if (is_nan($expected)) {
            $this->assertNan($result);
        } else {
            // The PHP testsuite performs assertions on the text representation of results. As we copied their test cases, we need to do the same.
            $this->assertSame((string) $expected, (string) $result);
        }
    }

    public static function fpowProvider(): iterable
    {
        // Cases tested in https://github.com/php/php-src/blob/php-8.4.2/ext/standard/tests/math/fpow.phpt
        yield [0, 0, 1];
        yield [0, 1, 0];
        yield [0, -1, INF];
        yield [0, 1.0, 0];
        yield [0, -1.0, INF];
        yield [0, 2, 0];
        yield [0, -2, INF];
        yield [0, 2.1, 0];
        yield [0, -2.1, INF];
        yield [0, 0.1, 0];
        yield [0, -0.1, INF];
        yield [0, 0.0, 1];
        yield [0, -0.0, 1];
        yield [0, 10, 0];
        yield [0, -10, INF];
        yield [0, INF, 0];
        yield [0, -INF, INF];
        yield [0, NAN, NAN];
        yield [1, 0, 1];
        yield [1, 1, 1];
        yield [1, -1, 1];
        yield [1, 1.0, 1];
        yield [1, -1.0, 1];
        yield [1, 2, 1];
        yield [1, -2, 1];
        yield [1, 2.1, 1];
        yield [1, -2.1, 1];
        yield [1, 0.1, 1];
        yield [1, -0.1, 1];
        yield [1, 0.0, 1];
        yield [1, -0.0, 1];
        yield [1, 10, 1];
        yield [1, -10, 1];
        yield [1, INF, 1];
        yield [1, -INF, 1];
        yield [1, NAN, 1];
        yield [-1, 0, 1];
        yield [-1, 1, -1];
        yield [-1, -1, -1];
        yield [-1, 1.0, -1];
        yield [-1, -1.0, -1];
        yield [-1, 2, 1];
        yield [-1, -2, 1];
        yield [-1, 2.1, NAN];
        yield [-1, -2.1, NAN];
        yield [-1, 0.1, NAN];
        yield [-1, -0.1, NAN];
        yield [-1, 0.0, 1];
        yield [-1, -0.0, 1];
        yield [-1, 10, 1];
        yield [-1, -10, 1];
        yield [-1, INF, 1];
        yield [-1, -INF, 1];
        yield [-1, NAN, NAN];
        yield [1.0, 0, 1];
        yield [1.0, 1, 1];
        yield [1.0, -1, 1];
        yield [1.0, 1.0, 1];
        yield [1.0, -1.0, 1];
        yield [1.0, 2, 1];
        yield [1.0, -2, 1];
        yield [1.0, 2.1, 1];
        yield [1.0, -2.1, 1];
        yield [1.0, 0.1, 1];
        yield [1.0, -0.1, 1];
        yield [1.0, 0.0, 1];
        yield [1.0, -0.0, 1];
        yield [1.0, 10, 1];
        yield [1.0, -10, 1];
        yield [1.0, INF, 1];
        yield [1.0, -INF, 1];
        yield [1.0, NAN, 1];
        yield [-1.0, 0, 1];
        yield [-1.0, 1, -1];
        yield [-1.0, -1, -1];
        yield [-1.0, 1.0, -1];
        yield [-1.0, -1.0, -1];
        yield [-1.0, 2, 1];
        yield [-1.0, -2, 1];
        yield [-1.0, 2.1, NAN];
        yield [-1.0, -2.1, NAN];
        yield [-1.0, 0.1, NAN];
        yield [-1.0, -0.1, NAN];
        yield [-1.0, 0.0, 1];
        yield [-1.0, -0.0, 1];
        yield [-1.0, 10, 1];
        yield [-1.0, -10, 1];
        yield [-1.0, INF, 1];
        yield [-1.0, -INF, 1];
        yield [-1.0, NAN, NAN];
        yield [2, 0, 1];
        yield [2, 1, 2];
        yield [2, -1, 0.5];
        yield [2, 1.0, 2];
        yield [2, -1.0, 0.5];
        yield [2, 2, 4];
        yield [2, -2, 0.25];
        yield [2, 2.1, 4.2870938501452];
        yield [2, -2.1, 0.2332582478842];
        yield [2, 0.1, 1.0717734625363];
        yield [2, -0.1, 0.93303299153681];
        yield [2, 0.0, 1];
        yield [2, -0.0, 1];
        yield [2, 10, 1024];
        yield [2, -10, 0.0009765625];
        yield [2, INF, INF];
        yield [2, -INF, 0];
        yield [2, NAN, NAN];
        yield [-2, 0, 1];
        yield [-2, 1, -2];
        yield [-2, -1, -0.5];
        yield [-2, 1.0, -2];
        yield [-2, -1.0, -0.5];
        yield [-2, 2, 4];
        yield [-2, -2, 0.25];
        yield [-2, 2.1, NAN];
        yield [-2, -2.1, NAN];
        yield [-2, 0.1, NAN];
        yield [-2, -0.1, NAN];
        yield [-2, 0.0, 1];
        yield [-2, -0.0, 1];
        yield [-2, 10, 1024];
        yield [-2, -10, 0.0009765625];
        yield [-2, INF, INF];
        yield [-2, -INF, 0];
        yield [-2, NAN, NAN];
        yield [2.1, 0, 1];
        yield [2.1, 1, 2.1];
        yield [2.1, -1, 0.47619047619048];
        yield [2.1, 1.0, 2.1];
        yield [2.1, -1.0, 0.47619047619048];
        yield [2.1, 2, 4.41];
        yield [2.1, -2, 0.22675736961451];
        yield [2.1, 2.1, 4.7496380917422];
        yield [2.1, -2.1, 0.21054235726688];
        yield [2.1, 0.1, 1.0770154403044];
        yield [2.1, -0.1, 0.92849179554696];
        yield [2.1, 0.0, 1];
        yield [2.1, -0.0, 1];
        yield [2.1, 10, 1667.9880978201];
        yield [2.1, -10, 0.0005995246616609];
        yield [2.1, INF, INF];
        yield [2.1, -INF, 0];
        yield [2.1, NAN, NAN];
        yield [-2.1, 0, 1];
        yield [-2.1, 1, -2.1];
        yield [-2.1, -1, -0.47619047619048];
        yield [-2.1, 1.0, -2.1];
        yield [-2.1, -1.0, -0.47619047619048];
        yield [-2.1, 2, 4.41];
        yield [-2.1, -2, 0.22675736961451];
        yield [-2.1, 2.1, NAN];
        yield [-2.1, -2.1, NAN];
        yield [-2.1, 0.1, NAN];
        yield [-2.1, -0.1, NAN];
        yield [-2.1, 0.0, 1];
        yield [-2.1, -0.0, 1];
        yield [-2.1, 10, 1667.9880978201];
        yield [-2.1, -10, 0.0005995246616609];
        yield [-2.1, INF, INF];
        yield [-2.1, -INF, 0];
        yield [-2.1, NAN, NAN];
        yield [0.1, 0, 1];
        yield [0.1, 1, 0.1];
        yield [0.1, -1, 10];
        yield [0.1, 1.0, 0.1];
        yield [0.1, -1.0, 10];
        yield [0.1, 2, 0.01];
        yield [0.1, -2, 100];
        yield [0.1, 2.1, 0.0079432823472428];
        yield [0.1, -2.1, 125.89254117942];
        yield [0.1, 0.1, 0.79432823472428];
        yield [0.1, -0.1, 1.2589254117942];
        yield [0.1, 0.0, 1];
        yield [0.1, -0.0, 1];
        yield [0.1, 10, 1.0E-10];
        yield [0.1, -10, 10000000000];
        yield [0.1, INF, 0];
        yield [0.1, -INF, INF];
        yield [0.1, NAN, NAN];
        yield [-0.1, 0, 1];
        yield [-0.1, 1, -0.1];
        yield [-0.1, -1, -10];
        yield [-0.1, 1.0, -0.1];
        yield [-0.1, -1.0, -10];
        yield [-0.1, 2, 0.01];
        yield [-0.1, -2, 100];
        yield [-0.1, 2.1, NAN];
        yield [-0.1, -2.1, NAN];
        yield [-0.1, 0.1, NAN];
        yield [-0.1, -0.1, NAN];
        yield [-0.1, 0.0, 1];
        yield [-0.1, -0.0, 1];
        yield [-0.1, 10, 1.0E-10];
        yield [-0.1, -10, 10000000000];
        yield [-0.1, INF, 0];
        yield [-0.1, -INF, INF];
        yield [-0.1, NAN, NAN];
        yield [0.0, 0, 1];
        yield [0.0, 1, 0];
        yield [0.0, -1, INF];
        yield [0.0, 1.0, 0];
        yield [0.0, -1.0, INF];
        yield [0.0, 2, 0];
        yield [0.0, -2, INF];
        yield [0.0, 2.1, 0];
        yield [0.0, -2.1, INF];
        yield [0.0, 0.1, 0];
        yield [0.0, -0.1, INF];
        yield [0.0, 0.0, 1];
        yield [0.0, -0.0, 1];
        yield [0.0, 10, 0];
        yield [0.0, -10, INF];
        yield [0.0, INF, 0];
        yield [0.0, -INF, INF];
        yield [0.0, NAN, NAN];
        yield [-0.0, 0, 1];
        yield [-0.0, 1, -0.0];
        yield [-0.0, -1, -INF];
        yield [-0.0, 1.0, -0.0];
        yield [-0.0, -1.0, -INF];
        yield [-0.0, 2, 0];
        yield [-0.0, -2, INF];
        yield [-0.0, 2.1, 0];
        yield [-0.0, -2.1, INF];
        yield [-0.0, 0.1, 0];
        yield [-0.0, -0.1, INF];
        yield [-0.0, 0.0, 1];
        yield [-0.0, -0.0, 1];
        yield [-0.0, 10, 0];
        yield [-0.0, -10, INF];
        yield [-0.0, INF, 0];
        yield [-0.0, -INF, INF];
        yield [-0.0, NAN, NAN];
        yield [10, 0, 1];
        yield [10, 1, 10];
        yield [10, -1, 0.1];
        yield [10, 1.0, 10];
        yield [10, -1.0, 0.1];
        yield [10, 2, 100];
        yield [10, -2, 0.01];
        yield [10, 2.1, 125.89254117942];
        yield [10, -2.1, 0.0079432823472428];
        yield [10, 0.1, 1.2589254117942];
        yield [10, -0.1, 0.79432823472428];
        yield [10, 0.0, 1];
        yield [10, -0.0, 1];
        yield [10, 10, 10000000000];
        yield [10, -10, 1.0E-10];
        yield [10, INF, INF];
        yield [10, -INF, 0];
        yield [10, NAN, NAN];
        yield [-10, 0, 1];
        yield [-10, 1, -10];
        yield [-10, -1, -0.1];
        yield [-10, 1.0, -10];
        yield [-10, -1.0, -0.1];
        yield [-10, 2, 100];
        yield [-10, -2, 0.01];
        yield [-10, 2.1, NAN];
        yield [-10, -2.1, NAN];
        yield [-10, 0.1, NAN];
        yield [-10, -0.1, NAN];
        yield [-10, 0.0, 1];
        yield [-10, -0.0, 1];
        yield [-10, 10, 10000000000];
        yield [-10, -10, 1.0E-10];
        yield [-10, INF, INF];
        yield [-10, -INF, 0];
        yield [-10, NAN, NAN];
        yield [INF, 0, 1];
        yield [INF, 1, INF];
        yield [INF, -1, 0];
        yield [INF, 1.0, INF];
        yield [INF, -1.0, 0];
        yield [INF, 2, INF];
        yield [INF, -2, 0];
        yield [INF, 2.1, INF];
        yield [INF, -2.1, 0];
        yield [INF, 0.1, INF];
        yield [INF, -0.1, 0];
        yield [INF, 0.0, 1];
        yield [INF, -0.0, 1];
        yield [INF, 10, INF];
        yield [INF, -10, 0];
        yield [INF, INF, INF];
        yield [INF, -INF, 0];
        yield [INF, NAN, NAN];
        yield [-INF, 0, 1];
        yield [-INF, 1, -INF];
        yield [-INF, -1, -0.0];
        yield [-INF, 1.0, -INF];
        yield [-INF, -1.0, -0.0];
        yield [-INF, 2, INF];
        yield [-INF, -2, 0];
        yield [-INF, 2.1, INF];
        yield [-INF, -2.1, 0];
        yield [-INF, 0.1, INF];
        yield [-INF, -0.1, 0];
        yield [-INF, 0.0, 1];
        yield [-INF, -0.0, 1];
        yield [-INF, 10, INF];
        yield [-INF, -10, 0];
        yield [-INF, INF, INF];
        yield [-INF, -INF, 0];
        yield [-INF, NAN, NAN];
        yield [NAN, 0, 1];
        yield [NAN, 1, NAN];
        yield [NAN, -1, NAN];
        yield [NAN, 1.0, NAN];
        yield [NAN, -1.0, NAN];
        yield [NAN, 2, NAN];
        yield [NAN, -2, NAN];
        yield [NAN, 2.1, NAN];
        yield [NAN, -2.1, NAN];
        yield [NAN, 0.1, NAN];
        yield [NAN, -0.1, NAN];
        yield [NAN, 0.0, 1];
        yield [NAN, -0.0, 1];
        yield [NAN, 10, NAN];
        yield [NAN, -10, NAN];
        yield [NAN, INF, NAN];
        yield [NAN, -INF, NAN];
        yield [NAN, NAN, NAN];
    }

    /**
     * @dataProvider graphemeStrSplitDataProvider
     */
    public function testGraphemeStrSplit(string $string, int $length, array $expectedValues)
    {
        $this->assertSame($expectedValues, grapheme_str_split($string, $length));
    }

    public static function graphemeStrSplitDataProvider(): array
    {
        $cases = [
            ['', 1, []],
            ['PHP', 1, ['P', 'H', 'P']],
            ['你好', 1, ['你', '好']],
            ['අයේෂ්', 1, ['අ', 'යේ', 'ෂ්']],
            ['สวัสดี', 2, ['สวั', 'สดี']],
        ];

        if (70300 <= PHP_VERSION_ID) {
            $cases[] = ['土下座🙇‍♀を', 1, ["土", "下", "座", "🙇‍♀", "を"]];
        }

        // Fixed in https://github.com/PCRE2Project/pcre2/issues/410
        if (defined('PCRE_VERSION_MAJOR') && 10 < PCRE_VERSION_MAJOR && 44 < PCRE_VERSION_MINOR) {
            $cases[] = ['👭🏻👰🏿‍♂️', 2, ['👭🏻', '👰🏿‍♂️']];
        }

        return $cases;
    }

    /**
     * @requires extension bcmath
     *
     * @covers \Symfony\Polyfill\Php84\Php84::bcdivmod
     *
     * @dataProvider bcDivModProvider
     */
    public function testBcDivMod(string $num1, string $num2, ?int $scale, array $expected)
    {
        $this->assertSame($expected, bcdivmod($num1, $num2, $scale));
    }

    /**
     * @requires extension bcmath
     */
    public function testBcDivModDivideByZero()
    {
        $this->expectException(\DivisionByZeroError::class);

        bcdivmod('1', '0');
    }

    /**
     * @requires extension bcmath
     */
    public function testBcDivModDivideByFloatingZero()
    {
        $this->expectException(\DivisionByZeroError::class);

        bcdivmod('1', '0.00');
    }

    /**
     * @requires PHP 8.0
     * @requires extension bcmath
     */
    public function testBcDivModMalformedNumber()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Argument #1 ($num1) is not well-formed');

        bcdivmod('a', '1');
    }

    /**
     * @requires PHP 8.0
     * @requires extension bcmath
     */
    public function testBcDivModMalformedNumber2()
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Argument #2 ($num2) is not well-formed');

        bcdivmod('1', 'a');
    }

    public static function bcDivModProvider(): iterable
    {
        yield ['1', '1', null, ['1', '0']];
        yield ['1', '2', null, ['0', '1']];
        yield ['5', '2', null, ['2', '1']];
        yield ['5', '2', 0, ['2', '1']];
        yield ['5', '2', 1, ['2', '1.0']];
        yield ['5', '2', 2, ['2', '1.00']];
        yield ['7.2', '3', 2, ['2', '1.20']];
    }
}
