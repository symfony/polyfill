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

        $p_orig = (\Transliterator::create($rules));

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
    }
}
