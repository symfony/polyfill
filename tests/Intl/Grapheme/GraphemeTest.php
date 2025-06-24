<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Intl\Grapheme;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Intl\Grapheme\Grapheme as p;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::<!public>
 */
class GraphemeTest extends TestCase
{
    /**
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_extract
     */
    public function testGraphemeExtractArrayError()
    {
        grapheme_extract('', 0);
        if (80000 > \PHP_VERSION_ID) {
            $this->assertFalse(@grapheme_extract([], 0));

            $this->expectWarning();
            $this->expectWarningMessage('expects parameter 1 to be string, array given');
        } else {
            $this->expectException(\TypeError::class);
        }
        grapheme_extract([], 0);
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_extract
     */
    public function testGraphemeExtract()
    {
        $this->assertFalse(grapheme_extract('', 0));
        $this->assertSame('', grapheme_extract('abc', 0));

        $this->assertSame('국어', grapheme_extract('한국어', 2, \GRAPHEME_EXTR_COUNT, 3, $next));
        $this->assertSame(9, $next);

        $next = 0;
        $this->assertSame('한', grapheme_extract('한국어', 1, \GRAPHEME_EXTR_COUNT, $next, $next));
        $this->assertSame('국', grapheme_extract('한국어', 1, \GRAPHEME_EXTR_COUNT, $next, $next));
        $this->assertSame('어', grapheme_extract('한국어', 1, \GRAPHEME_EXTR_COUNT, $next, $next));
        $this->assertFalse(grapheme_extract('한국어', 1, \GRAPHEME_EXTR_COUNT, $next, $next));

        $this->assertSame(str_repeat('-', 69000), grapheme_extract(str_repeat('-', 70000), 69000, \GRAPHEME_EXTR_COUNT));

        $this->assertSame('d', grapheme_extract('déjà', 2, \GRAPHEME_EXTR_MAXBYTES));
        $this->assertSame('dé', grapheme_extract('déjà', 2, \GRAPHEME_EXTR_MAXCHARS));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_extract
     */
    public function testGraphemeExtractWithInvalidType()
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
            $this->expectExceptionMessage('grapheme_extract(): Argument #3 ($type) must be one of GRAPHEME_EXTR_COUNT, GRAPHEME_EXTR_MAXBYTES, or GRAPHEME_EXTR_MAXCHARS');
        }

        $this->assertFalse(grapheme_extract('abc', 1, -1));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_extract
     */
    public function testGraphemeExtractWithNegativeStart()
    {
        $this->assertSame('j', grapheme_extract('déjà', 2, \GRAPHEME_EXTR_MAXBYTES, -3, $next));
        $this->assertSame(4, $next);
        $this->assertSame('jà', grapheme_extract('déjà', 2, \GRAPHEME_EXTR_MAXCHARS, -3));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_strlen
     */
    public function testGraphemeStrlen()
    {
        $this->assertSame(3, grapheme_strlen('한국어'));
        $this->assertSame(3, grapheme_strlen(\Normalizer::normalize('한국어', \Normalizer::NFD)));

        $this->assertNull(grapheme_strlen("\xE9"));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_substr
     */
    public function testGraphemeSubstr()
    {
        $c = 'déjà';

        $this->assertSame('jà', grapheme_substr($c, -2, null));

        if (\PHP_VERSION_ID >= 80000) {
            $this->assertSame('jà', grapheme_substr($c, -2, 3));
            $this->assertSame('', grapheme_substr($c, -1, 0));
            $this->assertSame('', grapheme_substr($c, 1, -4));
        } else {
            // See http://bugs.php.net/62759 and 55562
            $this->assertSame('jà', grapheme_substr($c, -2, 3));
            $this->assertSame('', grapheme_substr($c, -1, 0));
            $this->assertFalse(grapheme_substr($c, 1, -4));
        }

        $this->assertSame('jà', grapheme_substr($c, 2));
        $this->assertSame('jà', grapheme_substr($c, -2));
        $this->assertSame('j', grapheme_substr($c, -2, -1));
        $this->assertSame('', grapheme_substr($c, -2, -2));

        $this->assertSame('☎', grapheme_substr('☢☎❄', 1, 1));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_substr
     *
     * @requires PHP < 8
     */
    public function testGraphemeSubstrReturnsFalsePrePHP8()
    {
        $c = 'déjà';
        $this->assertFalse(grapheme_substr($c, 5, 1));
        $this->assertFalse(grapheme_substr($c, -5, 1));
        $this->assertFalse(grapheme_substr($c, -42, 1));
        $this->assertFalse(grapheme_substr($c, 42, 5));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_substr
     *
     * @requires PHP 8
     */
    public function testGraphemeSubstrReturnsEmptyPostPHP8()
    {
        $c = 'déjà';
        $this->assertSame('', grapheme_substr($c, 5, 1));
        $this->assertSame('d', grapheme_substr($c, -5, 1));
        $this->assertSame('d', grapheme_substr($c, -42, 1));
        $this->assertSame('', grapheme_substr($c, 42, 5));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_strpos
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_stripos
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_strrpos
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_strripos
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_position
     */
    public function testGraphemeStrpos()
    {
        if (80000 > \PHP_VERSION_ID) {
            $this->assertFalse(grapheme_strpos('abc', ''));
        } else {
            $this->assertSame(0, grapheme_strpos('abc', ''));
        }
        $this->assertFalse(grapheme_strpos('abc', 'd'));
        $this->assertFalse(grapheme_strpos('abc', 'a', 3));
        $this->assertFalse(grapheme_strpos('abc', 'a', -1));
        $this->assertSame(1, grapheme_strpos('한국어', '국'));
        $this->assertSame(3, grapheme_stripos('DÉJÀ', 'à'));
        if (80000 > \PHP_VERSION_ID) {
            $this->assertFalse(grapheme_strrpos('한국어', ''));
        } else {
            $this->assertSame(3, grapheme_strrpos('한국어', ''));
        }
        $this->assertSame(1, grapheme_strrpos('한국어', '국'));
        $this->assertSame(3, grapheme_strripos('DÉJÀ', 'à'));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_strpos
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_stripos
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_strrpos
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_strripos
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_position
     */
    public function testGraphemeStrposWithNegativeOffset()
    {
        $this->assertSame(3, grapheme_strpos('abca', 'a', -1));
        $this->assertSame(3, grapheme_stripos('abca', 'A', -1));

        $this->assertSame(4, grapheme_strripos('DEJAA', 'a'));
        $this->assertSame(3, grapheme_strripos('DEJAA', 'a', -2));
        $this->assertFalse(grapheme_strripos('DEJAA', 'a', -3));

        $this->assertSame(4, grapheme_strrpos('DEJAA', 'A'));
        $this->assertSame(3, grapheme_strrpos('DEJAA', 'A', -2));
        $this->assertFalse(grapheme_strrpos('DEJAA', 'A', -3));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_strstr
     * @covers \Symfony\Polyfill\Intl\Grapheme\Grapheme::grapheme_stristr
     */
    public function testGraphemeStrstr()
    {
        $this->assertSame('국어', grapheme_strstr('한국어', '국'));
        $this->assertSame('ÉJÀ', grapheme_stristr('DÉJÀ', 'é'));
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
        if (defined('PCRE_VERSION_MAJOR') && PCRE_VERSION_MAJOR > 10 && PCRE_VERSION_MINOR > 44) {
            $cases[] = ['👭🏻👰🏿‍♂️', 2, ['👭🏻', '👰🏿‍♂️']];
        }

        return $cases;
    }
}
