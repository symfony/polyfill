<?php

/*
 * Copyright (c) 2014 TrueServer B.V.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * Originally forked from
 * https://github.com/true/php-punycode/blob/v2.1.1/tests/PunycodeTest.php
 */

namespace Symfony\Polyfill\Tests\Intl\Idn;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Intl\Idn\Idn;

/**
 * @author Renan Gonçalves <renan.saddam@gmail.com>
 * @author Sebastian Kroczek <sk@xbug.de>
 * @author Dmitry Lukashin <dmitry@lukashin.ru>
 * @author Fahad Ibnay Heylaal <fahad19@gmail.com>
 * @author ceeram <c33ram@gmail.com>
 * @author Laurent Bassin <laurent@bassin.info>
 *
 * @covers \Symfony\Polyfill\Intl\Idn\Idn::<!public>
 *
 * @requires PHP 5.4
 */
class IdnTest extends TestCase
{
    /**
     * @group legacy
     * @dataProvider domainNamesProvider
     */
    public function testEncode2003($decoded, $encoded)
    {
        $result = @idn_to_ascii($decoded, IDNA_DEFAULT, INTL_IDNA_VARIANT_2003);
        $this->assertSame($encoded, $result);
    }

    /**
     * @dataProvider invalidUtf8DomainNamesProvider
     */
    public function testEncodeInvalid($decoded)
    {
        $result = idn_to_ascii($decoded, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        $this->assertFalse($result);
    }

    /**
     * @group legacy
     * @dataProvider domainNamesProvider
     */
    public function testDecode2003($decoded, $encoded)
    {
        $result = @idn_to_utf8($encoded, IDNA_DEFAULT, INTL_IDNA_VARIANT_2003);
        $this->assertSame($decoded, $result);
    }

    /**
     * @group legacy
     * @dataProvider invalidAsciiDomainName2003Provider
     */
    public function testDecodeInvalid2003($encoded, $expected)
    {
        $result = @idn_to_utf8($encoded, IDNA_DEFAULT, INTL_IDNA_VARIANT_2003);
        $this->assertSame($expected, $result);
    }

    /**
     * @group legacy
     * @dataProvider domainNamesUppercase2003Provider
     */
    public function testUppercase2003($decoded, $ascii, $encoded)
    {
        $result = @idn_to_ascii($decoded, IDNA_DEFAULT, INTL_IDNA_VARIANT_2003);
        $this->assertSame($ascii, $result);

        $result = @idn_to_utf8($ascii, IDNA_DEFAULT, INTL_IDNA_VARIANT_2003);
        $this->assertSame($encoded, $result);
    }

    /**
     * @dataProvider domainNamesProvider
     */
    public function testEncodeUTS46($decoded, $encoded)
    {
        $result = idn_to_ascii($decoded, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        $this->assertSame($encoded, $result);
    }

    /**
     * @dataProvider domainNamesProvider
     */
    public function testDecodeUTS46($decoded, $encoded)
    {
        $result = idn_to_utf8($encoded, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        $this->assertSame($decoded, $result);
    }

    /**
     * @dataProvider domainNamesUppercaseUTS46Provider
     */
    public function testUppercaseUTS46($decoded, $ascii, $encoded)
    {
        $info = 123;
        $result = idn_to_ascii($decoded, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $info);
        $this->assertSame($ascii, $result);

        $expected = array(
            'result' => $result,
            'isTransitionalDifferent' => false,
            'errors' => 0,
        );
        $this->assertSame($expected, $info);

        $info = 123;
        $result = idn_to_utf8($ascii, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46, $info);
        $this->assertSame($encoded, $result);

        $expected = array(
            'result' => $result,
            'isTransitionalDifferent' => false,
            'errors' => 0,
        );
        $this->assertSame($expected, $info);
    }

    /**
     * @group legacy
     * @dataProvider domainNamesProvider
     */
    public function testEncodePhp53($decoded, $encoded)
    {
        $result = @idn_to_ascii($decoded, IDNA_DEFAULT);
        $this->assertSame($encoded, $result);
    }

    public function domainNamesProvider()
    {
        return array(
            array(
                'foo.example.',
                'foo.example.',
            ),
            // https://en.wikipedia.org/wiki/IDN_Test_TLDs
            array(
                'مثال.إختبار',
                'xn--mgbh0fb.xn--kgbechtv',
            ),
            array(
                'مثال.آزمایشی',
                'xn--mgbh0fb.xn--hgbk6aj7f53bba',
            ),
            array(
                '例子.测试',
                'xn--fsqu00a.xn--0zwm56d',
            ),
            array(
                '例子.測試',
                'xn--fsqu00a.xn--g6w251d',
            ),
            array(
                'пример.испытание',
                'xn--e1afmkfd.xn--80akhbyknj4f',
            ),
            array(
                'उदाहरण.परीक्षा',
                'xn--p1b6ci4b4b3a.xn--11b5bs3a9aj6g',
            ),
            array(
                'παράδειγμα.δοκιμή',
                'xn--hxajbheg2az3al.xn--jxalpdlp',
            ),
            array(
                '실례.테스트',
                'xn--9n2bp8q.xn--9t4b11yi5a',
            ),
            array(
                'בײַשפּיל.טעסט',
                'xn--fdbk5d8ap9b8a8d.xn--deba0ad',
            ),
            array(
                '例え.テスト',
                'xn--r8jz45g.xn--zckzah',
            ),
            array(
                'உதாரணம்.பரிட்சை',
                'xn--zkc6cc5bi7f6e.xn--hlcj6aya9esc7a',
            ),

            array(
                'derhausüberwacher.de',
                'xn--derhausberwacher-pzb.de',
            ),
            array(
                'renangonçalves.com',
                'xn--renangonalves-pgb.com',
            ),
            array(
                'рф.ru',
                'xn--p1ai.ru',
            ),
            array(
                'δοκιμή.gr',
                'xn--jxalpdlp.gr',
            ),
            array(
                'ফাহাদ্১৯.বাংলা',
                'xn--65bj6btb5gwimc.xn--54b7fta0cc',
            ),
            array(
                '𐌀𐌖𐌋𐌄𐌑𐌉·𐌌𐌄𐌕𐌄𐌋𐌉𐌑.gr',
                'xn--uba5533kmaba1adkfh6ch2cg.gr',
            ),
            array(
                'guangdong.广东',
                'guangdong.xn--xhq521b',
            ),
            array(
                'gwóźdź.pl',
                'xn--gwd-hna98db.pl',
            ),
            array(
                'άέήίΰαβγδεζηθικλμνξοπρσστυφχ.com',
                'xn--hxacdefghijklmnopqrstuvw0caz0a1a2a.com',
            ),
        );
    }

    public function domainNamesUppercase2003Provider()
    {
        return array(
            array(
                'рф.RU',
                'xn--p1ai.RU',
                'рф.RU',
            ),
            array(
                'GUANGDONG.广东',
                'GUANGDONG.xn--xhq521b',
                'GUANGDONG.广东',
            ),
            array(
                'renanGonçalves.COM',
                'xn--renangonalves-pgb.COM',
                'renangonçalves.COM',
            ),
        );
    }

    public function domainNamesUppercaseUTS46Provider()
    {
        return array(
            array(
                'рф.RU',
                'xn--p1ai.ru',
                'рф.ru',
            ),
            array(
                'GUANGDONG.广东',
                'guangdong.xn--xhq521b',
                'guangdong.广东',
            ),
            array(
                'renanGonçalves.COM',
                'xn--renangonalves-pgb.com',
                'renangonçalves.com',
            ),
        );
    }

    public function invalidUtf8DomainNamesProvider()
    {
        return array(
            array(
                'äöüßáàăâåãąāæćĉčċçďđéèĕêěëėęēğĝġģĥħíìĭîïĩįīıĵķĺľļłńňñņŋóòŏôőõøōœĸŕřŗśŝšşťţŧúùŭûůűũųūŵýŷÿźžżðþ.de',
            ),
            array(
                'aaaaa.aaaaaaaaaaaaaaa.aaaaaaaaaaaaaaaaaaa.aaaaaaaaaaaaaaaaa.aaaaaaaaaaaaaaaaaa.äöüßáàăâåãąāæćĉčċçďđéèĕêěëėęēğĝġģĥ.ħíìĭîïĩįīıĵķĺľļłńňñņŋóòŏôőõ.øōœĸŕřŗśŝšşťţŧúùŭûůűũųū.ŵýŷÿźžżðþ.de',
            ),
            array(
                'aa..aa.de',
            ),
        );
    }

    public function invalidAsciiDomainName2003Provider()
    {
        return array(
            array(
                'xn--zcaccffbljjkknnoorrssuuxxd5e0a0a3ae9c6a4a9bzdzdxdudwdxd2d2d8d0dse7d6dwe9dxeueweye4eyewe9e5ewkkewc9ftfpfplwexfwf4infvf2f6f6f7f8fpg8fmgngrgrgvgzgygxg3gyg1g3g5gykqg9g.de',
                'xn--zcaccffbljjkknnoorrssuuxxd5e0a0a3ae9c6a4a9bzdzdxdudwdxd2d2d8d0dse7d6dwe9dxeueweye4eyewe9e5ewkkewc9ftfpfplwexfwf4infvf2f6f6f7f8fpg8fmgngrgrgvgzgygxg3gyg1g3g5gykqg9g.de',
            ),
            array(
                'xn--zcaccffbljjkknnoorrssuuxxd5e0a0a3ae9c8c1b0dxdvdvdxdvd3d0d6dyd8d5d4due7dveseuewe2eweue7e3esk9dxc7frf9e7kuevfuf1ilftf5f4f4f5f6fng6f8f9fpgpgtgxgwgvg1g2gzg1g3gvkog7g.xn--vda.de',
                'xn--zcaccffbljjkknnoorrssuuxxd5e0a0a3ae9c8c1b0dxdvdvdxdvd3d0d6dyd8d5d4due7dveseuewe2eweue7e3esk9dxc7frf9e7kuevfuf1ilftf5f4f4f5f6fng6f8f9fpgpgtgxgwgvg1g2gzg1g3gvkog7g.þ.de',
            ),
        );
    }
}
