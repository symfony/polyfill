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
    private $old_mb_internal_encoding;

    public function setUp()
    {
        if (function_exists('mb_internal_encoding')) {
            $this->old_mb_internal_encoding = mb_internal_encoding();
            mb_internal_encoding('UTF-8');
        }
    }

    public function tearDown()
    {
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding($this->old_mb_internal_encoding);
        }
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Transliterator\Transliterator::create
     */
    public function testTransliteratorCreate()
    {
        // https://unicode.org/cldr/utility/transform.jsp?a=NFKC%3B+%5B%3ANonspacing+Mark%3A%5D+Remove%3B+NFKC%3B+Any-Latin%3B+Latin-ASCII%3B&b=&show=on
        $rules = 'NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII;';

        $p = p::create($rules);

        $this->assertSame('NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII', $p->id);

        if (class_exists('Transliterator')) {
            $p_orig = \Transliterator::create($rules);

            $this->assertSame(
                str_replace(' ', '', $p_orig->id),
                str_replace(' ', '', $p->id)
            );
        }
    }

    public function testTransliteratorIdIsReadOnly()
    {
        $str = '‹ŤÉŚŢ› - öäü - 123 - abc - …';
        $rules = 'LOWER();';

        if (class_exists('Transliterator')) {
            $p_orig = \Transliterator::create($rules);

            $p = p::create($rules);

            $this->assertSame($p_orig->id, $p->id);

            @$p->id = 'UPPER;';
            @$p_orig->id = 'UPPER;';

            $this->assertSame('LOWER()', $p->id);
            $this->assertSame($p_orig->id, $p->id);

            $this->assertSame('‹ťéśţ› - öäü - 123 - abc - …', $p->transliterate($str));
            $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
        } else {
            $p = p::create($rules);

            @$p->id = 'UPPER;';

            $this->assertSame('LOWER()', $p->id);

            $this->assertSame('‹ťéśţ› - öäü - 123 - abc - …', $p->transliterate($str));
        }
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Transliterator\Transliterator::transliterate
     */
    public function testTransliteratorTransliterate()
    {
        // https://unicode.org/cldr/utility/transform.jsp?a=NFKC%3B+%5B%3ANonspacing+Mark%3A%5D+Remove%3B+NFKC%3B+Any-Upper%3B+Any-Latin%3B+Latin-ASCII%3B+%5BAU%5D+lower%28%29%3B&b=%E2%80%B9%C5%A4%C3%89%C5%9A%C5%A2%E2%80%BA+-+%C3%B6%C3%A4%C3%BC+-+123+-+abc+-+%E2%80%A6&show=on
        $rules = 'NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Upper; Any-Latin; Latin-ASCII;  [AU] lower() ;';
        $str = '‹ŤÉŚŢ› - öäü - 123 - abc - …';

        $p = p::create($rules);

        $this->assertSame('<TEST> - Oau - 123 - aBC - ...', $p->transliterate($str));

        if (class_exists('Transliterator')) {
            $p_orig = \Transliterator::create($rules);

            $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
        }

        // ---

        $rules = 'Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove (); Lower();';
        $str = '‹ŤÉŚŢ› - öäü - 123 - abc - …';

        $p = p::create($rules);

        $this->assertSame('test  oau  123  abc  ', $p->transliterate($str));

        if (class_exists('Transliterator')) {
            $p_orig = \Transliterator::create($rules);

            $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
        }

        // ---

        $rules = 'Any-Latin; Latin-ASCII; [^a-zA-Z[:space separator:]] Any-remove';
        $str = 'A æ Übérmensch på høyeste nivå! И я люблю PHP! ест. ﬁ ... Ѕ Э Ы Щ ш Ч Ц џ ќ ѓ';

        $p = p::create($rules);

        $this->assertSame('A ae Ubermensch pa hoyeste niva I a lublu PHP est fi  Z E Y S s C C d k g', $p->transliterate($str));

        if (class_exists('Transliterator')) {
            $p_orig = \Transliterator::create($rules);

            $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
        }
    }

    public function testTransliteratorNull()
    {
        $rules = ':: [ŤÄ] lower();';

        $p = p::create($rules);

        $this->assertNull($p);

        if (class_exists('Transliterator')) {
            $p_orig = \Transliterator::create($rules);

            $this->assertSame($p_orig, $p);
        }

        // ---

        $rules = 'lower();';

        $p = p::createFromRules($rules);

        $this->assertNull($p);

        if (class_exists('Transliterator')) {
            $p_orig = \Transliterator::createFromRules($rules);

            $this->assertSame($p_orig, $p);
        }
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Transliterator\Transliterator::transliterate
     */
    public function testTransliteratorTransliterateAndRemove()
    {
        // https://unicode.org/cldr/utility/transform.jsp?a=Any-Upper%3B+%5B%5E%5Cx20%5Cx65%5Cx69%5Cx61%5Cx73%5Cx6E%5Cx74%5Cx72%5Cx6F%5Cx6C%5Cx75%5Cx64%5Cx5D%5Cx5B%5Cx63%5Cx6D%5Cx70%5Cx27%5Cx0A%5Cx67%5Cx7C%5Cx68%5Cx76%5Cx2E%5Cx66%5Cx62%5Cx2C%5Cx3A%5Cx3D%5Cx2D%5Cx71%5Cx31%5Cx30%5Cx43%5Cx32%5Cx2A%5Cx79%5Cx78%5Cx29%5Cx28%5Cx4C%5Cx39%5Cx41%5Cx53%5Cx2F%5Cx50%5Cx22%5Cx45%5Cx6A%5Cx4D%5Cx49%5Cx6B%5Cx33%5Cx3E%5Cx35%5Cx54%5Cx3C%5Cx44%5Cx34%5Cx7D%5Cx42%5Cx7B%5Cx38%5Cx46%5Cx77%5Cx52%5Cx36%5Cx37%5Cx55%5Cx47%5Cx4E%5Cx3B%5Cx4A%5Cx7A%5Cx56%5Cx23%5Cx48%5Cx4F%5Cx57%5Cx5F%5Cx26%5Cx21%5Cx4B%5Cx3F%5Cx58%5Cx51%5Cx25%5Cx59%5Cx5C%5Cx09%5Cx5A%5Cx2B%5Cx7E%5Cx5E%5Cx24%5Cx40%5Cx60%5Cx7F%5Cx00%5Cx01%5Cx02%5Cx03%5Cx04%5Cx05%5Cx06%5Cx07%5Cx08%5Cx0B%5Cx0C%5Cx0D%5Cx0E%5Cx0F%5Cx10%5Cx11%5Cx12%5Cx13%5Cx14%5Cx15%5Cx16%5Cx17%5Cx18%5Cx19%5Cx1A%5Cx1B%5Cx1C%5Cx1D%5Cx1E%5Cx1F%5D+Remove%3B&b=%E2%80%B9%C5%A4%C3%89%C5%9A%C5%A2%E2%80%BA+-+%C3%B6%C3%A4%C3%BC+-+123+-+abc+-+%E2%80%A6&show=on
        $rules = 'Any-Upper; [^\x20\x65\x69\x61\x73\x6E\x74\x72\x6F\x6C\x75\x64\x5D\x5B\x63\x6D\x70\x27\x0A\x67\x7C\x68\x76\x2E\x66\x62\x2C\x3A\x3D\x2D\x71\x31\x30\x43\x32\x2A\x79\x78\x29\x28\x4C\x39\x41\x53\x2F\x50\x22\x45\x6A\x4D\x49\x6B\x33\x3E\x35\x54\x3C\x44\x34\x7D\x42\x7B\x38\x46\x77\x52\x36\x37\x55\x47\x4E\x3B\x4A\x7A\x56\x23\x48\x4F\x57\x5F\x26\x21\x4B\x3F\x58\x51\x25\x59\x5C\x09\x5A\x2B\x7E\x5E\x24\x40\x60\x7F\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F] Remove;';
        $str = '‹ŤÉŚŢ› - öäü - 123 - abc - …';

        $p = p::create($rules);

        $this->assertSame(' -  - 123 - ABC - ', $p->transliterate($str));

        if (class_exists('Transliterator')) {
            $p_orig = \Transliterator::create($rules);

            $this->assertSame(trim($p_orig->transliterate($str)), trim($p->transliterate($str)));
        }

        // ---

        // regex without "^"
        $rules = 'Any-Upper; [\x20\x65\x69\x61\x73\x6E\x74\x72\x6F\x6C\x75\x64\x5D\x5B\x63\x6D\x70\x27\x0A\x67\x7C\x68\x76\x2E\x66\x62\x2C\x3A\x3D\x2D\x71\x31\x30\x43\x32\x2A\x79\x78\x29\x28\x4C\x39\x41\x53\x2F\x50\x22\x45\x6A\x4D\x49\x6B\x33\x3E\x35\x54\x3C\x44\x34\x7D\x42\x7B\x38\x46\x77\x52\x36\x37\x55\x47\x4E\x3B\x4A\x7A\x56\x23\x48\x4F\x57\x5F\x26\x21\x4B\x3F\x58\x51\x25\x59\x5C\x09\x5A\x2B\x7E\x5E\x24\x40\x60\x7F\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F] Remove;';
        $str = '‹ŤÉŚŢ› - öäü - 123 - abc - …';

        $p = p::create($rules);

        $this->assertSame('‹ŤÉŚŢ›ÖÄÜ…', $p->transliterate($str));

        if (class_exists('Transliterator')) {
            $p_orig = \Transliterator::create($rules);

            $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
        }
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Transliterator\Transliterator::createFromRules
     */
    public function testTransliteratorTransliterateCreateFromRules()
    {
        // https://unicode.org/cldr/utility/transform.jsp?a=%5B%5B%3APunctuation%3A%5D%5B%3ASpace%3A%5D%5D%2B+%3E+%27+%27%3B&b=%E2%80%B9%C5%A4%C3%89%C5%9A%C5%A2%E2%80%BA+-+%C3%B6%C3%A4%C3%BC+-+123+-+abc+-+%E2%80%A6&show=on
        $rules = '[[:Punctuation:][:Space:]]+ > \' \';';
        $str = '‹ŤÉŚŢ› - öäü - 123 - abc - …';

        $p = p::createFromRules($rules);

        $this->assertSame(' ŤÉŚŢ öäü 123 abc ', $p->transliterate($str));

        if (class_exists('Transliterator')) {
            $p_orig = \Transliterator::createFromRules($rules);

            $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
        }

        // ---

        // https://unicode.org/cldr/utility/transform.jsp?a=%3A%3A+NFD+%28NFC%29+%3B&b=%E2%80%B9%C5%A4%C3%89%C5%9A%C5%A2%E2%80%BA+-+%C3%B6%C3%A4%C3%BC+-+123+-+abc+-+%E2%80%A6&show=on
        $rules = 'ä <>f;'; // http://userguide.icu-project.org/transforms/general/rules
        $str = '‹ŤÉŚŢ› - öäü - 123 - abc - …';

        $p = p::createFromRules($rules);

        $this->assertSame('‹ŤÉŚŢ› - öfü - 123 - abc - …', $p->transliterate($str));

        if (class_exists('Transliterator')) {
            $p_orig = \Transliterator::createFromRules($rules);

            $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
        }

        // ---

        $rules = ' ä >;'; // http://userguide.icu-project.org/transforms/general/rules
        $str = '‹ŤÉŚŢ› - öäü - 123 - abc - …';

        $p = p::createFromRules($rules);

        $this->assertSame('‹ŤÉŚŢ› - öü - 123 - abc - …', $p->transliterate($str));

        if (class_exists('Transliterator')) {
            $p_orig = \Transliterator::createFromRules($rules);

            $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
        }

        // ---

        // https://unicode.org/cldr/utility/transform.jsp?a=%3A%3A+%5B%C5%A4%C3%84%5D+lower%28%29%3B&b=%E2%80%B9%C5%A4%C3%89%C5%9A%C5%A2%E2%80%BA+-+%C3%96%C3%84%C3%9C+-+123+-+abc+-+%E2%80%A6&show=on
        $rules = ':: [ŤÄ] lower();'; // http://userguide.icu-project.org/transforms/general/rules
        $str = '‹ŤÉŚŢ› - ÖÄÜ - 123 - abc - …';

        $p = p::createFromRules($rules);

        $this->assertSame('‹ťÉŚŢ› - ÖäÜ - 123 - abc - …', $p->transliterate($str));

        if (class_exists('Transliterator')) {
            $p_orig = \Transliterator::createFromRules($rules);

            $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
        }
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Transliterator\Transliterator::transliterate
     */
    public function testTransliteratorTransliterateForGermanLanguage()
    {
        // https://unicode.org/cldr/utility/transform.jsp?a=Any-Lower%3B+de-ASCII%3B&b=%C5%A4%C3%89%C5%9A%C5%A2+-+%C3%B6%C3%A4%C3%BC+-+123+-+abc+-+%E2%80%A6&show=on
        $rules = 'Any-Lower; de-ASCII;';
        $str = 'ŤÉŚŢ - öäü - 123 - abc - …';

        $p = p::create($rules);

        $this->assertSame('test - oeaeue - 123 - abc - ...', $p->transliterate($str));

        /*
        if (class_exists('Transliterator')) {
            $p_orig = (\Transliterator::create($rules));

            // TODO? -> this is not working on travis-ci -> old ICU version -> https://github.com/unicode-org/cldr/blob/master/common/transforms/de-ASCII.xml
            $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
        }
        */
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Transliterator\Transliterator::transliterate
     */
    public function testTransliteratorTransliterateForTurkmenLanguage()
    {
        // https://unicode.org/cldr/utility/transform.jsp?a=Turkmen-Latin%2FBGN%3B+Latin-ASCII%3B&b=%C5%A4%C3%89%C5%9A%C5%A2+-+%C3%B6%C3%A4%C3%BC+-+123+-+abc+-+%E2%80%A6&show=on
        $rules = 'Turkmen-Latin/BGN; Latin-ASCII;';
        $str = 'ŤÉŚŢ - öäü - 123 - abc - …';
        $str_len = mb_strlen($str);

        $p = p::create($rules);

        $this->assertSame('TEST - oau - 123 - abc - ...', $p->transliterate($str));
        $this->assertSame('ŤÉŚŢ - oau - 123 - abc - ...', $p->transliterate($str, 5));
        $this->assertSame('ŤÉŚŢ - oau - 123 - abc - …', $p->transliterate($str, 5, 10));
        $this->assertSame('TEST - oau - 123 - abc - ...', $p->transliterate($str, 0));
        $this->assertFalse($p->transliterate($str, $str_len + 1));
        $this->assertSame('ŤÉŚŢ - öäü - 123 - abc - …', $p->transliterate($str, $str_len, $str_len));
        $this->assertSame('ŤÉŚŢ - öäü - 123 - abc - …', $p->transliterate($str, $str_len, $str_len));
        $this->assertFalse($p->transliterate($str, 2, -2));
        $this->assertFalse($p->transliterate($str, -2, 2));
        $this->assertSame('TEST - oau - 123 - abc - …', $p->transliterate($str, null, 10));

        if (class_exists('Transliterator')) {
            $p_orig = \Transliterator::create($rules);

            $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
            $this->assertSame($p_orig->transliterate($str, 5), $p->transliterate($str, 5));
            $this->assertSame($p_orig->transliterate($str, 5, 10), $p->transliterate($str, 5, 10));
            $this->assertSame($p_orig->transliterate($str, 0), $p->transliterate($str, 0));
            $this->assertSame($p_orig->transliterate($str, $str_len + 1), $p->transliterate($str, $str_len + 1));
            $this->assertSame($p_orig->transliterate($str, $str_len, $str_len), $p->transliterate($str, $str_len, $str_len));
            $this->assertSame($p_orig->transliterate($str, $str_len, $str_len), $p->transliterate($str, $str_len, $str_len));
            $this->assertSame($p_orig->transliterate($str, 2, -2), $p->transliterate($str, 2, -2));
            $this->assertSame($p_orig->transliterate($str, -2, 2), $p->transliterate($str, -2, 2));
            $this->assertSame($p_orig->transliterate($str, null, 10), $p->transliterate($str, null, 10));
        }
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Transliterator\Transliterator::getLanguage
     */
    public function testTransliteratorGetLanguage()
    {
        if (class_exists('Transliterator')) {
            $id_array_orig = transliterator_list_ids();
            $this->assertTrue(\is_array($id_array_orig) && \count($id_array_orig) > 1);
        }

        $id_array = p::listIDs();
        $this->assertTrue(\is_array($id_array) && \count($id_array) > 1);
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
