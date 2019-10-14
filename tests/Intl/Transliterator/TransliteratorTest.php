<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Intl\Transliterator;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Intl\Transliterator\Transliterator as p;

/**
 * @covers \Symfony\Polyfill\Intl\Transliterator\Transliterator::<!public>
 */
class TransliteratorTest extends TestCase
{
    /**
     * @covers \Symfony\Polyfill\Intl\Transliterator\Transliterator::create
     */
    public function testTransliteratorCreate()
    {
        $intl_support = \extension_loaded('intl');
        if (false === $intl_support) {
            $this->markTestSkipped('intl is not installed');
        }

        // https://unicode.org/cldr/utility/transform.jsp?a=NFKC%3B+%5B%3ANonspacing+Mark%3A%5D+Remove%3B+NFKC%3B+Any-Latin%3B+Latin-ASCII%3B&b=&show=on
        $rules = 'NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII;';

        $p = p::create($rules);

        $p_orig = \Transliterator::create($rules);

        $this->assertSame($p_orig->id, $p->id);
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Transliterator\Transliterator::transliterate
     */
    public function testTransliteratorTransliterate()
    {
        $intl_support = \extension_loaded('intl');
        if (false === $intl_support) {
            $this->markTestSkipped('intl is not installed');
        }

        // https://unicode.org/cldr/utility/transform.jsp?a=NFKC%3B+%5B%3ANonspacing+Mark%3A%5D+Remove%3B+NFKC%3B+Any-Upper%3B+Any-Latin%3B+Latin-ASCII%3B&b=%E2%80%B9%C5%A4%C3%89%C5%9A%C5%A2%E2%80%BA+-+%C3%B6%C3%A4%C3%BC+-+123+-+abc+-+%E2%80%A6&show=on
        $rules = 'NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Upper; Any-Latin; Latin-ASCII;';
        $str = '‹ŤÉŚŢ› - öäü - 123 - abc - …';

        $p = p::create($rules);

        $p_orig = \Transliterator::create($rules);

        $this->assertSame('<TEST> - OAU - 123 - ABC - ...', $p->transliterate($str));
        $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Transliterator\Transliterator::transliterate
     */
    public function testTransliteratorTransliterateForGermanLanguage()
    {
        $intl_support = \extension_loaded('intl');
        if (false === $intl_support) {
            $this->markTestSkipped('intl is not installed');
        }

        // https://unicode.org/cldr/utility/transform.jsp?a=NFC%3B+%5B%3ANonspacing+Mark%3A%5D+Remove%3B+NFC%3B+Any-Lower%3B+Any-Latin%3B+de-ascii%3B&b=%C5%A4%C3%89%C5%9A%C5%A2+-+%C3%B6%C3%A4%C3%BC+-+123+-+abc+-+%E2%80%A6&show=on
        $rules = 'NFC; [:Nonspacing Mark:] Remove; NFC; Any-Lower; Any-Latin; de-ascii;';
        $str = 'ŤÉŚŢ - öäü - 123 - abc - …';

        $p = p::create($rules);

        //$p_orig = (\Transliterator::create($rules));

        $this->assertSame('test - oeaeue - 123 - abc - ...', $p->transliterate($str));
        // TODO? -> this is not working on travis-ci -> missing language stuff ??
        //$this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Transliterator\Transliterator::transliterate
     */
    public function testTransliteratorTransliterateForTurkmenLanguage()
    {
        $intl_support = \extension_loaded('intl');
        if (false === $intl_support) {
            $this->markTestSkipped('intl is not installed');
        }

        // https://unicode.org/cldr/utility/transform.jsp?a=Turkmen-Latin%2FBGN%3B+Latin-ASCII%3B&b=%C5%A4%C3%89%C5%9A%C5%A2+-+%C3%B6%C3%A4%C3%BC+-+123+-+abc+-+%E2%80%A6&show=on
        $rules = 'Turkmen-Latin/BGN; Latin-ASCII;';
        $str = 'ŤÉŚŢ - öäü - 123 - abc - …';

        $p = p::create($rules);

        $p_orig = \Transliterator::create($rules);

        $this->assertSame('TEST - oau - 123 - abc - ...', $p->transliterate($str));
        $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));

        $this->assertSame('ŤÉŚŢ - oau - 123 - abc - ...', $p->transliterate($str, 5));
        $this->assertSame($p_orig->transliterate($str, 5), $p->transliterate($str, 5));

        $this->assertSame('ŤÉŚŢ - oau - 123 - abc - …', $p->transliterate($str, 5, 10));
        $this->assertSame($p_orig->transliterate($str, 5, 10), $p->transliterate($str, 5, 10));

        $this->assertSame('TEST - oau  - abc - …', $p->transliterate($str, null, 10));
        //$this->assertSame($p_orig->transliterate($str, null, 10), $p->transliterate($str, null, 10)); // TODO? -> error from "transliterate" itself?
    }

    public function stringProvider()
    {
        $tests = array(
            // Valid defaults
            array('', ''),
            array(' ', ' '),
            array(null, ''),
            array('1a', '1a'),
            array('2a', '2a'),
            array('+1', '+1'),
            array("      - abc- \xc2\x87", '      - abc- ++'),
            array('abc', 'abc'),
            // Valid UTF-8
            array('أبز', 'abz'),
            array("\xe2\x80\x99", '\''),
            array('Ɓtest', 'Btest'),
            array('  -ABC-中文空白-  ', '  -ABC-Zhong Wen Kong Bai -  '),
            array('deja vu', 'deja vu'),
            array('déjà vu ', 'deja vu '),
            array('déjà σσς iıii', 'deja sss iiii'),
            array("test\x80-\xBFöäü", ''),
            array('Internationalizaetion', 'Internationalizaetion'),
            array("中 - &#20013; - %&? - \xc2\x80", 'Zhong  - &#20013; - %&? - EUR'),
            array('Un été brûlant sur la côte', 'Un ete brulant sur la cote'),
            array('Αυτή είναι μια δοκιμή', 'Auti inai mia dokimi'),
            array('أحبك', 'ahbk'),
            array('キャンパス', 'kiyanpasu'),
            array('биологическом', 'biologiceskom'),
            array('정, 병호', 'jeong, byeongho'),
            array('ますだ, よしひこ', 'masuda, yosihiko'),
            array('मोनिच', 'MaoNaiCa'),
            array('क्षȸ', 'KaShhadb'),
            array('أحبك 😀', 'ahbk 😀'),
            array('∀ i ∈ ℕ', '∀ i ∈ N'),
            array('👍 💩 😄 ❤ 👍 💩 😄 ❤أحبك', '👍 💩 😄 ❤ 👍 💩 😄 ❤ahbk'),
            array('纳达尔绝境下大反击拒绝冷门逆转晋级中网四强', 'Na Da Er Jue Jing Xia Da Fan Ji Ju Jue Leng Men Ni Zhuan Jin Ji Zhong Wang Si Qiang '),
            array('κόσμε', 'kosme'),
            array('中', 'Zhong '),
            array('«foobar»', '<<foobar>>'),
            // Valid UTF-8 + UTF-8 NO-BREAK SPACE
            array("κόσμε\xc2\xa0", 'kosme '),
            // Valid UTF-8 + Invalid Chars
            array("κόσμε\xa0\xa1-öäü", ''),
            // Valid UTF-8 + ISO-Errors
            array('DÃ¼sseldorf', 'DA1/4sseldorf'),
            // Valid invisible char
            array('<x%0Conxxx=1', '<x%0Conxxx=1'),
            // Valid ASCII
            array('a', 'a'),
            // Valid emoji (non-UTF-8)
            array('😃', '😃'),
            array('🐵 🙈 🙉 🙊 | ❤️ 💔 💌 💕 💞 💓 💗 💖 💘 💝 💟 💜 💛 💚 💙 | 🚾 🆒 🆓 🆕 🆖 🆗 🆙 🏧', '🐵 🙈 🙉 🙊 | ❤ 💔 💌 💕 💞 💓 💗 💖 💘 💝 💟 💜 💛 💚 💙 | 🚾 🆒 🆓 🆕 🆖 🆗 🆙 🏧'),
            // Valid ASCII + Invalid Chars
            array("a\xa0\xa1-öäü", ''),
            // Valid 2 Octet Sequence
            array("\xc3\xb1", 'n'), // ñ
            // Invalid 2 Octet Sequence
            array("\xc3\x28", ''),
            // Invalid
            array("\x00", "\x00"),
            array("a\xDFb", ''),
            // Invalid Sequence Identifier
            array("\xa0\xa1", ''),
            // Valid 3 Octet Sequence
            array("\xe2\x82\xa1", 'CL'),
            // Invalid 3 Octet Sequence (in 2nd Octet)
            array("\xe2\x28\xa1", ''),
            // Invalid 3 Octet Sequence (in 3rd Octet)
            array("\xe2\x82\x28", ''),
            // Valid 4 Octet Sequence
            array("\xf0\x90\x8c\xbc", '𐌼'),
            // Invalid 4 Octet Sequence (in 2nd Invalid 4 Octet Sequence (in 2ndOctet)
            array("\xf0\x28\x8c\xbc", ''),
            // Invalid 4 Octet Sequence (in 3rd Octet)
            array("\xf0\x90\x28\xbc", ''),
            // Invalid 4 Octet Sequence (in 4th Octet)
            array("\xf0\x28\x8c\x28", ''),
            // Valid 5 Octet Sequence (but not Unicode!)
            array("\xf8\xa1\xa1\xa1\xa1", ''),
            // Valid 6 Octet Sequence (but not Unicode!)
            array("\xfc\xa1\xa1\xa1\xa1\xa1", ''),
            // Valid 6 Octet Sequence (but not Unicode!) + UTF-8 EN SPACE
            array("\xfc\xa1\xa1\xa1\xa1\xa1\xe2\x80\x82", ''),
        );

        return $tests;
    }

    /**
     * @dataProvider stringProvider()
     *
     * @param string $str
     * @param string $expected
     */
    public function testWithDifferentStrings($str, $expected)
    {
        $rules = 'NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII;';

        $p = p::create($rules);

        for ($i = 0; $i <= 1; ++$i) { // keep this loop for simple performance tests
            $this->assertSame($expected, $p->transliterate($str));
        }
    }
}
