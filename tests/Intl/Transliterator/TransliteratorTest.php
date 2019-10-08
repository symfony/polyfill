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
        if ($intl_support === false) {
            $this->markTestSkipped('intl is not installed');
        }

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
        if ($intl_support === false) {
            $this->markTestSkipped('intl is not installed');
        }

        $rules = 'NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII;';
        $str = 'ŤÉŚŢ - öäü - 123 - abc - …';

        $p = p::create($rules);

        $p_orig = \Transliterator::create($rules);

        $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Transliterator\Transliterator::transliterate
     */
    public function testTransliteratorTransliterateForGermanLanguage()
    {
        $intl_support = \extension_loaded('intl');
        if ($intl_support === false) {
            $this->markTestSkipped('intl is not installed');
        }

        $rules = 'NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; de-ascii;';
        $str = 'ŤÉŚŢ - öäü - 123 - abc - …';

        $p = p::create($rules);

        $p_orig = \Transliterator::create($rules);

        $this->assertSame('TEST - oeaeue - 123 - abc - ...', $p->transliterate($str));
        $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
    }

    /**
     * @covers \Symfony\Polyfill\Intl\Transliterator\Transliterator::transliterate
     */
    public function testTransliteratorTransliterateForTurkmenLanguage()
    {
        $intl_support = \extension_loaded('intl');
        if ($intl_support === false) {
            $this->markTestSkipped('intl is not installed');
        }

        $rules = 'Turkmen-Latin/BGN; Latin-ASCII;';
        $str = 'ŤÉŚŢ - öäü - 123 - abc - …';

        $p = p::create($rules);

        $p_orig = \Transliterator::create($rules);

        $this->assertSame('TEST - oau - 123 - abc - ...', $p->transliterate($str));
        $this->assertSame($p_orig->transliterate($str), $p->transliterate($str));
    }
}
