<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com> and Trevor Rowbotham <trevor.rowbotham@pm.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Intl\Idn;

use PHPUnit\Framework\TestCase;
use RuntimeException;
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
 */
class IdnTest extends TestCase
{
    private static $ERROR_CODE_MAP = array(
        'P1' => Idn::ERROR_DISALLOWED,
        'P4' => array(
            Idn::ERROR_EMPTY_LABEL,
            Idn::ERROR_DOMAIN_NAME_TOO_LONG,
            Idn::ERROR_LABEL_TOO_LONG,
            Idn::ERROR_PUNYCODE,
        ),
        'V1' => Idn::ERROR_INVALID_ACE_LABEL,
        'V2' => Idn::ERROR_HYPHEN_3_4,
        'V3' => array(Idn::ERROR_LEADING_HYPHEN, Idn::ERROR_TRAILING_HYPHEN),
        'V4' => Idn::ERROR_LABEL_HAS_DOT,
        'V5' => Idn::ERROR_LEADING_COMBINING_MARK,
        'V6' => Idn::ERROR_DISALLOWED,
        // V7 and V8 are handled by C* and B* respectively.
        'A3' => Idn::ERROR_PUNYCODE,
        'A4_1' => Idn::ERROR_DOMAIN_NAME_TOO_LONG,
        'A4_2' => array(Idn::ERROR_EMPTY_LABEL, Idn::ERROR_LABEL_TOO_LONG),
        'B1' => Idn::ERROR_BIDI,
        'B2' => Idn::ERROR_BIDI,
        'B3' => Idn::ERROR_BIDI,
        'B4' => Idn::ERROR_BIDI,
        'B5' => Idn::ERROR_BIDI,
        'B6' => Idn::ERROR_BIDI,
        'C1' => Idn::ERROR_CONTEXTJ,
        'C2' => Idn::ERROR_CONTEXTJ,
        // ContextO isn't tested here.
        // 'C3' => Idn::ERROR_CONTEXTO_PUNCTUATION,
        // 'C4' => Idn::ERROR_CONTEXTO_PUNCTUATION,
        // 'C5' => Idn::ERROR_CONTEXTO_PUNCTUATION,
        // 'C6' => Idn::ERROR_CONTEXTO_PUNCTUATION,
        // 'C7' => Idn::ERROR_CONTEXTO_PUNCTUATION,
        // 'C8' => Idn::ERROR_CONTEXTO_DIGITS,
        // 'C9' => Idn::ERROR_CONTEXTO_DIGITS,
        'X4_2' => Idn::ERROR_EMPTY_LABEL,
        'X3' => Idn::ERROR_EMPTY_LABEL,
    );

    /**
     * @return array<int, array{0: string, 1: string, 2: array<int, int|array<int, int>>, 3: string, 4: array<int, int|array<int, int>>, 5: string, 6: array<int, int|array<int, int>>}>
     */
    public function getData()
    {
        $h = fopen(__DIR__.'/IdnaTestV2.txt', 'r');
        $tests = array();

        while (false !== ($line = fgets($h))) {
            if ("\n" === $line || '#' === $line[0]) {
                continue;
            }

            list($line) = explode('#', $line);
            list($source, $toUnicode, $toUnicodeStatus, $toAsciiN, $toAsciiNStatus, $toAsciiT, $toAsciiTStatus) = array_map('trim', explode(';', $line));

            if ('' === $toUnicode) {
                $toUnicode = $source;
            }

            if ('' === $toAsciiN) {
                $toAsciiN = $toUnicode;
            }

            if ('' === $toAsciiT) {
                $toAsciiT = $toAsciiN;
            }

            $toUnicodeStatus = $this->resolveErrorCodes($toUnicodeStatus, array());
            $toAsciiNStatus = $this->resolveErrorCodes($toAsciiNStatus, $toUnicodeStatus);
            $toAsciiTStatus = $this->resolveErrorCodes($toAsciiTStatus, $toAsciiNStatus);
            $tests[] = array($source, $toUnicode, $toUnicodeStatus, $toAsciiN, $toAsciiNStatus, $toAsciiT, $toAsciiTStatus);
        }

        fclose($h);

        return $tests;
    }

    /**
     * @requires PHP 7.1
     * @dataProvider getData
     *
     * @param string                          $source
     * @param string                          $toUnicode
     * @param array<int, int|array<int, int>> $toUnicodeStatus
     * @param string                          $toAsciiN
     * @param array<int, int|array<int, int>> $toAsciiNStatus
     * @param string                          $toAsciiT
     * @param array<int, int|array<int, int>> $toAsciiTStatus
     */
    public function testToUnicode($source, $toUnicode, $toUnicodeStatus, $toAsciiN, $toAsciiNStatus, $toAsciiT, $toAsciiTStatus)
    {
        $options = IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ | IDNA_USE_STD3_RULES | IDNA_NONTRANSITIONAL_TO_UNICODE;
        $result = idn_to_utf8($source, $options, INTL_IDNA_VARIANT_UTS46, $info);

        if ($info === null) {
            $this->markTestSkipped('PHP Bug #72506.');
        }

        if ($toUnicodeStatus === array()) {
            $this->assertSame($toUnicode, $info['result']);
            $this->assertSame(0, $info['errors'], sprintf('Expected no errors, but found %d.', $info['errors']));
        } else {
            $this->assertNotSame(0, $info['errors'], 'Expected to find errors, but found none.');
        }
    }

    /**
     * @requires PHP 7.1
     * @dataProvider getData
     *
     * @param string                          $source
     * @param string                          $toUnicode
     * @param array<int, int|array<int, int>> $toUnicodeStatus
     * @param string                          $toAsciiN
     * @param array<int, int|array<int, int>> $toAsciiNStatus
     * @param string                          $toAsciiT
     * @param array<int, int|array<int, int>> $toAsciiTStatus
     */
    public function testToAsciiNonTransitional($source, $toUnicode, $toUnicodeStatus, $toAsciiN, $toAsciiNStatus, $toAsciiT, $toAsciiTStatus)
    {
        $options = IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ | IDNA_USE_STD3_RULES | IDNA_NONTRANSITIONAL_TO_ASCII;
        $result = idn_to_ascii($source, $options, INTL_IDNA_VARIANT_UTS46, $info);

        if ($info === null) {
            $this->markTestSkipped('PHP Bug #72506.');
        }

        if ($toAsciiNStatus === array()) {
            $this->assertSame($toAsciiN, $info['result']);
            $this->assertSame(0, $info['errors'], sprintf('Expected no errors, but found %d.', $info['errors']));
        } else {
            $this->assertNotSame(0, $info['errors'], 'Expected to find errors, but found none.');
        }
    }

    /**
     * @requires PHP 7.1
     * @dataProvider getData
     *
     * @param string                          $source
     * @param string                          $toUnicode
     * @param array<int, int|array<int, int>> $toUnicodeStatus
     * @param string                          $toAsciiN
     * @param array<int, int|array<int, int>> $toAsciiNStatus
     * @param string                          $toAsciiT
     * @param array<int, int|array<int, int>> $toAsciiTStatus
     */
    public function testToAsciiTransitional($source, $toUnicode, $toUnicodeStatus, $toAsciiN, $toAsciiNStatus, $toAsciiT, $toAsciiTStatus)
    {
        $options = IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ | IDNA_USE_STD3_RULES;
        $result = idn_to_ascii($source, $options, INTL_IDNA_VARIANT_UTS46, $info);

        if ($info === null) {
            $this->markTestSkipped('PHP Bug #72506.');
        }

        // There is currently a bug in the test data, where it is expected that the following 2
        // source strings result in an empty string. However, due to the way the test files are setup
        // it currently isn't possible to represent an empty string as an expected value. So, we
        // skip these 2 problem tests. I have notified the Unicode Consortium about this and they
        // have passed the information along to the spec editors.
        // U+200C or U+200D
        if ("\xE2\x80\x8C" === $source || "\xE2\x80\x8D" === $source) {
            $toAsciiT = '';
        }

        if ($toAsciiTStatus === array()) {
            $this->assertSame($toAsciiT, $info['result']);
            $this->assertSame(0, $info['errors'], sprintf('Expected no errors, but found %d.', $info['errors']));
        } else {
            $this->assertNotSame(0, $info['errors'], 'Expected to find errors, but found none.');
        }
    }

    /**
     * @requires PHP 5.4
     * @requires PHP < 8
     * @group legacy
     * @dataProvider domainNamesProvider
     */
    public function testEncode2003($decoded, $encoded)
    {
        $result = @idn_to_ascii($decoded, IDNA_DEFAULT, INTL_IDNA_VARIANT_2003);
        $this->assertSame($encoded, $result);
    }

    /**
     * @requires PHP 5.4
     * @dataProvider invalidUtf8DomainNamesProvider
     */
    public function testEncodeInvalid($decoded)
    {
        $result = idn_to_ascii($decoded, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        $this->assertFalse($result);
    }

    /**
     * @requires PHP 5.4
     * @requires PHP < 8
     * @group legacy
     * @dataProvider domainNamesProvider
     */
    public function testDecode2003($decoded, $encoded)
    {
        $result = @idn_to_utf8($encoded, IDNA_DEFAULT, INTL_IDNA_VARIANT_2003);
        $this->assertSame($decoded, $result);
    }

    /**
     * @requires PHP 5.4
     * @dataProvider domainNamesProvider
     */
    public function testEncodeUTS46($decoded, $encoded)
    {
        $result = idn_to_ascii($decoded, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        $this->assertSame($encoded, $result);
    }

    /**
     * @requires PHP 5.4
     * @dataProvider domainNamesProvider
     */
    public function testDecodeUTS46($decoded, $encoded)
    {
        $result = idn_to_utf8($encoded, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        $this->assertSame($decoded, $result);
    }

    /**
     * @requires PHP 5.4
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
     * @requires PHP < 8
     * @group legacy
     * @dataProvider domainNamesProvider
     */
    public function testEncodePhp53($decoded, $encoded)
    {
        $result = @Idn::idn_to_ascii($decoded, IDNA_DEFAULT, INTL_IDNA_VARIANT_2003);
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
            array(
                'test@bücher.de',
                'xn--test@bcher-feb.de',
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
            array(
                '-leading.de',
            ),
            array(
                'trailing-.de',
            ),
        );
    }

    /**
     * @param array<int, int|array<int, int>> $inherit
     *
     * @return array<int, int|array<int, int>>
     */
    private function resolveErrorCodes($statusCodes, $inherit)
    {
        if ('' === $statusCodes) {
            return $inherit;
        }

        if ('[]' === $statusCodes) {
            return array();
        }

        $matchCount = preg_match_all('/[PVUABCX][0-9](?:_[0-9])?/', $statusCodes, $matches);

        if (PREG_NO_ERROR !== preg_last_error()) {
            throw new RuntimeException();
        }

        if (0 === $matchCount) {
            throw new RuntimeException();
        }

        $errors = array();

        foreach ($matches[0] as $match) {
            if ('U' === $match[0]) {
                continue;
            }

            if (!isset(self::$ERROR_CODE_MAP[$match])) {
                throw new \RuntimeException(sprintf('Unhandled error code %s.', $match));
            }

            $errors[] = self::$ERROR_CODE_MAP[$match];
        }

        return $errors;
    }
}
