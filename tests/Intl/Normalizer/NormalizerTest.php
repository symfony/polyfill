<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Intl\Normalizer;

use Normalizer as in;
use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Intl\Normalizer\Normalizer as pn;
use Symfony\Polyfill\Util\TestListenerTrait;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @covers \Symfony\Polyfill\Intl\Normalizer\Normalizer::<!public>
 *
 * @requires extension intl
 */
class NormalizerTest extends TestCase
{
    public function testConstants()
    {
        $rpn = new \ReflectionClass('Symfony\Polyfill\Intl\Normalizer\Normalizer');
        $rin = new \ReflectionClass('Normalizer');

        $rpn = $rpn->getConstants();
        $rin = $rin->getConstants();

        unset($rin['NONE']);
        if (!\defined('Normalizer::FORM_KC_CF')) {
            unset($rpn['FORM_KC_CF'], $rpn['NFKC_CF']);
        }

        ksort($rpn);
        ksort($rin);

        $this->assertSame($rin, $rpn);
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Normalizer\Normalizer::isNormalized
     */
    public function testIsNormalized()
    {
        $c = 'déjà';
        $d = in::normalize($c, pn::NFD);

        $this->assertTrue(normalizer_is_normalized(''));
        $this->assertTrue(normalizer_is_normalized('abc'));
        $this->assertTrue(normalizer_is_normalized($c));
        $this->assertTrue(normalizer_is_normalized($c, pn::NFC));
        $this->assertFalse(normalizer_is_normalized($c, pn::NFD));
        $this->assertFalse(normalizer_is_normalized($d, pn::NFC));
        $this->assertFalse(normalizer_is_normalized("\xFF"));

        $this->assertTrue(pn::isNormalized($d, pn::NFD));

        $this->assertFalse(pn::isNormalized('', 42));

        if (\PHP_VERSION_ID >= 70300) {
            $this->assertTrue(normalizer_is_normalized('abc', pn::NFKC_CF));
            $this->assertFalse(normalizer_is_normalized('ABC', pn::NFKC_CF));
            $this->assertFalse(normalizer_is_normalized("\u{00DF}", pn::NFKC_CF));
            $this->assertFalse(normalizer_is_normalized("\u{FB01}", pn::NFKC_CF));
            $this->assertFalse(normalizer_is_normalized("\u{00AD}", pn::NFKC_CF));
            $this->assertTrue(normalizer_is_normalized('', pn::NFKC_CF));
        }
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Normalizer\Normalizer::normalize
     */
    public function testNormalize()
    {
        $c = in::normalize('déjà', pn::NFC).in::normalize('훈쇼™', pn::NFD);
        if (\PHP_VERSION_ID < 70300) {
            $this->assertSame($c, normalizer_normalize($c, in::NONE));
        }

        $c = 'déjà 훈쇼™';
        $d = in::normalize($c, pn::NFD);
        $kc = in::normalize($c, pn::NFKC);
        $kd = in::normalize($c, pn::NFKD);

        $this->assertSame('', normalizer_normalize(''));
        $this->assertSame($c, normalizer_normalize($d));
        $this->assertSame($c, normalizer_normalize($d, pn::NFC));
        $this->assertSame($d, normalizer_normalize($c, pn::NFD));
        $this->assertSame($kc, normalizer_normalize($d, pn::NFKC));
        $this->assertSame($kd, normalizer_normalize($c, pn::NFKD));

        $this->assertFalse(normalizer_normalize("\xFF"));

        $this->assertSame("\xcc\x83\xc3\x92\xd5\x9b", normalizer_normalize("\xcc\x83\xc3\x92\xd5\x9b"));
        $this->assertSame("\xe0\xbe\xb2\xe0\xbd\xb1\xe0\xbe\x80\xe0\xbe\x80", normalizer_normalize("\xe0\xbd\xb6\xe0\xbe\x81", pn::NFD));

        if (\PHP_VERSION_ID >= 70300) {
            $this->assertSame('abc', normalizer_normalize('ABC', pn::NFKC_CF));
            $this->assertSame('ss', normalizer_normalize("\u{00DF}", pn::NFKC_CF));
            $this->assertSame('fi', normalizer_normalize("\u{FB01}", pn::NFKC_CF));
            $this->assertSame("\u{03C9}", normalizer_normalize("\u{2126}", pn::NFKC_CF));
            $this->assertSame("\u{00E5}", normalizer_normalize("\u{0041}\u{030A}", pn::NFKC_CF));
            $this->assertSame('', normalizer_normalize("\u{00AD}", pn::NFKC_CF));
            $this->assertSame('', normalizer_normalize('', pn::NFKC_CF));
            $this->assertSame('abcssfiss', normalizer_normalize("ABC\u{00DF}\u{FB01}\u{1E9E}", pn::NFKC_CF));
        }
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Normalizer\Normalizer::normalize
     */
    public function testNormalizeWithInvalidForm()
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
            $this->expectExceptionMessage('normalizer_normalize(): Argument #2 ($form) must be a a valid normalization form');
        }

        $this->assertFalse(normalizer_normalize('foo', -1));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Normalizer\Normalizer::getRawDecomposition
     */
    public function testGetRawDecomposition()
    {
        $this->assertNull(normalizer_get_raw_decomposition('a'));
        $this->assertNull(normalizer_get_raw_decomposition('A'));

        $this->assertSame("A\xCC\x8A", normalizer_get_raw_decomposition("\u{00C5}"));
        $this->assertSame("a\xCC\x8A", normalizer_get_raw_decomposition("\u{00E5}"));
        $this->assertSame("\xC5\xBF\xCC\x87", normalizer_get_raw_decomposition("\u{1E9B}"));
        $this->assertSame("\xE0\xB2\xBF\xE0\xB3\x95", normalizer_get_raw_decomposition("\u{0CC0}"));
        $this->assertSame("\xCE\xAE", normalizer_get_raw_decomposition("\u{1F75}"));
        $this->assertSame("\xE3\x82\xAB\xE3\x82\x99", normalizer_get_raw_decomposition("\u{30AC}"));

        $this->assertNull(normalizer_get_raw_decomposition("\u{FB01}"));
        $this->assertNull(normalizer_get_raw_decomposition("\u{FB05}"));
        $this->assertNull(normalizer_get_raw_decomposition("\u{2026}"));
        $this->assertNull(normalizer_get_raw_decomposition("\u{FDFA}"));
        $this->assertNull(normalizer_get_raw_decomposition("\u{FFDA}"));

        $this->assertSame('fi', normalizer_get_raw_decomposition("\u{FB01}", pn::NFKC));
        $this->assertSame("\xC5\xBFt", normalizer_get_raw_decomposition("\u{FB05}", pn::NFKC));
        $this->assertSame('...', normalizer_get_raw_decomposition("\u{2026}", pn::NFKC));
        $this->assertSame("\xE3\x85\xA1", normalizer_get_raw_decomposition("\u{FFDA}", pn::NFKC));
        $this->assertSame("A\xCC\x8A", normalizer_get_raw_decomposition("\u{00C5}", pn::NFKC));
        $this->assertSame("A\xCC\x8A", normalizer_get_raw_decomposition("\u{00C5}", pn::NFKD));
        $this->assertSame("A\xCC\x8A", normalizer_get_raw_decomposition("\u{00C5}", pn::NFD));

        $this->assertSame("\xE1\x84\x80\xE1\x85\xA1", normalizer_get_raw_decomposition("\u{AC00}"));
        $this->assertSame("\xEA\xB0\x80\xE1\x86\xA8", normalizer_get_raw_decomposition("\u{AC01}"));
        $this->assertSame("\xED\x9E\x88\xE1\x87\x82", normalizer_get_raw_decomposition("\u{D7A3}"));
        $this->assertNull(normalizer_get_raw_decomposition("\u{D7A4}"));

        $this->assertNull(normalizer_get_raw_decomposition(''));
        $this->assertNull(normalizer_get_raw_decomposition('aa'));
        $this->assertNull(normalizer_get_raw_decomposition("\xFF"));
        $this->assertNull(normalizer_get_raw_decomposition("\xF5"));

        $this->assertSame('', normalizer_get_raw_decomposition("\u{00C5}", 0));
        $this->assertSame('', normalizer_get_raw_decomposition("\u{00C5}", -1));
        $this->assertSame('', normalizer_get_raw_decomposition("\u{00C5}", 999));
    }

    /**
     * @group legacy
     */
    public function testGetRawDecompositionNullArgument()
    {
        $this->assertNull(@normalizer_get_raw_decomposition(null));
        $this->assertSame('', @normalizer_get_raw_decomposition("\u{00C5}", null));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Normalizer\Normalizer::normalize
     */
    public function testNormalizeConformance()
    {
        if (false === TestListenerTrait::$enabledPolyfills) {
            $this->markTestSkipped('No need to test the native implementation');
        }

        $t = file(__DIR__.'/NormalizationTest.txt');
        $c = [];

        foreach ($t as $s) {
            $t = explode('#', $s);
            $t = explode(';', $t[0]);

            if (6 === \count($t)) {
                foreach ($t as $k => $s) {
                    $t = explode(' ', $s);
                    $t = array_map('hexdec', $t);
                    $t = array_map(__CLASS__.'::chr', $t);
                    $c[$k] = implode('', $t);
                }

                $this->assertSame($c[1], normalizer_normalize($c[0], pn::NFC));
                $this->assertSame($c[1], normalizer_normalize($c[1], pn::NFC));
                $this->assertSame($c[1], normalizer_normalize($c[2], pn::NFC));
                $this->assertSame($c[3], normalizer_normalize($c[3], pn::NFC));
                $this->assertSame($c[3], normalizer_normalize($c[4], pn::NFC));

                $this->assertSame($c[2], normalizer_normalize($c[0], pn::NFD));
                $this->assertSame($c[2], normalizer_normalize($c[1], pn::NFD));
                $this->assertSame($c[2], normalizer_normalize($c[2], pn::NFD));
                $this->assertSame($c[4], normalizer_normalize($c[3], pn::NFD));
                $this->assertSame($c[4], normalizer_normalize($c[4], pn::NFD));

                $this->assertSame($c[3], normalizer_normalize($c[0], pn::NFKC));
                $this->assertSame($c[3], normalizer_normalize($c[1], pn::NFKC));
                $this->assertSame($c[3], normalizer_normalize($c[2], pn::NFKC));
                $this->assertSame($c[3], normalizer_normalize($c[3], pn::NFKC));
                $this->assertSame($c[3], normalizer_normalize($c[4], pn::NFKC));

                $this->assertSame($c[4], normalizer_normalize($c[0], pn::NFKD));
                $this->assertSame($c[4], normalizer_normalize($c[1], pn::NFKD));
                $this->assertSame($c[4], normalizer_normalize($c[2], pn::NFKD));
                $this->assertSame($c[4], normalizer_normalize($c[3], pn::NFKD));
                $this->assertSame($c[4], normalizer_normalize($c[4], pn::NFKD));
            }
        }
    }

    private static function chr($c)
    {
        if (0x80 > $c %= 0x200000) {
            return \chr($c);
        }
        if (0x800 > $c) {
            return \chr(0xC0 | $c >> 6).\chr(0x80 | $c & 0x3F);
        }
        if (0x10000 > $c) {
            return \chr(0xE0 | $c >> 12).\chr(0x80 | $c >> 6 & 0x3F).\chr(0x80 | $c & 0x3F);
        }

        return \chr(0xF0 | $c >> 18).\chr(0x80 | $c >> 12 & 0x3F).\chr(0x80 | $c >> 6 & 0x3F).\chr(0x80 | $c & 0x3F);
    }
}
