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
    private static $ERROR_CODE_MAP = [
        'P1' => Idn::ERROR_DISALLOWED,
        'P4' => [
            Idn::ERROR_EMPTY_LABEL,
            Idn::ERROR_DOMAIN_NAME_TOO_LONG,
            Idn::ERROR_LABEL_TOO_LONG,
            Idn::ERROR_PUNYCODE,
        ],
        'V1' => Idn::ERROR_INVALID_ACE_LABEL,
        'V2' => Idn::ERROR_HYPHEN_3_4,
        'V3' => [Idn::ERROR_LEADING_HYPHEN, Idn::ERROR_TRAILING_HYPHEN],
        'V4' => Idn::ERROR_LABEL_HAS_DOT,
        'V5' => Idn::ERROR_LEADING_COMBINING_MARK,
        'V6' => Idn::ERROR_DISALLOWED,
        // V7 and V8 are handled by C* and B* respectively.
        'A3' => Idn::ERROR_PUNYCODE,
        'A4_1' => Idn::ERROR_DOMAIN_NAME_TOO_LONG,
        'A4_2' => [Idn::ERROR_EMPTY_LABEL, Idn::ERROR_LABEL_TOO_LONG],
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
    ];

    /**
     * @return array<int, array{0: string, 1: string, 2: array<int, int|array<int, int>>, 3: string, 4: array<int, int|array<int, int>>, 5: string, 6: array<int, int|array<int, int>>}>
     */
    public static function getData()
    {
        $h = fopen(__DIR__.'/IdnaTestV2.txt', 'r');
        $tests = [];

        while (false !== ($line = fgets($h))) {
            if ("\n" === $line || '#' === $line[0]) {
                continue;
            }

            [$line] = explode('#', $line);
            [$source, $toUnicode, $toUnicodeStatus, $toAsciiN, $toAsciiNStatus, $toAsciiT, $toAsciiTStatus] = preg_replace_callback(
                '/\\\\(?:u([[:xdigit:]]{4})|x{([[:xdigit:]]{4})})/u',
                static function (array $matches): string {
                    return mb_chr(hexdec($matches[1]), 'utf-8');
                },
                array_map('trim', explode(';', $line))
            );

            if ('' === $toUnicode) {
                $toUnicode = $source;
            }

            if ('' === $toAsciiN) {
                $toAsciiN = $toUnicode;
            }

            if ('' === $toAsciiT) {
                $toAsciiT = $toAsciiN;
            }

            $toUnicodeStatus = self::resolveErrorCodes($toUnicodeStatus, []);
            $toAsciiNStatus = self::resolveErrorCodes($toAsciiNStatus, $toUnicodeStatus);
            $toAsciiTStatus = self::resolveErrorCodes($toAsciiTStatus, $toAsciiNStatus);
            $tests[] = [$source, $toUnicode, $toUnicodeStatus, $toAsciiN, $toAsciiNStatus, $toAsciiT, $toAsciiTStatus];
        }

        fclose($h);

        return $tests;
    }

    /**
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
        if (\defined('INTL_ICU_VERSION') && version_compare(\INTL_ICU_VERSION, '74', '>=')) {
            $this->markTestSkipped('IdnaTestV2.txt is based on Unicode 13.0.0; ICU 74+ uses newer Unicode data.');
        }

        if (\PHP_VERSION_ID >= 80400 && '' === $source) {
            $this->expectException(\ValueError::class);
        }

        $options = \IDNA_CHECK_BIDI | \IDNA_CHECK_CONTEXTJ | \IDNA_USE_STD3_RULES | \IDNA_NONTRANSITIONAL_TO_UNICODE;
        idn_to_utf8($source, $options, \INTL_IDNA_VARIANT_UTS46, $info);

        if (null === $info) {
            $this->markTestSkipped('PHP Bug #72506.');
        }

        if ([] === $toUnicodeStatus) {
            $this->assertSame($toUnicode, $info['result']);
            $this->assertSame(0, $info['errors'], \sprintf('Expected no errors, but found %d.', $info['errors']));
        } else {
            $this->assertNotSame(0, $info['errors'], 'Expected to find errors, but found none.');
        }
    }

    /**
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
        if (\defined('INTL_ICU_VERSION') && version_compare(\INTL_ICU_VERSION, '74', '>=')) {
            $this->markTestSkipped('IdnaTestV2.txt is based on Unicode 13.0.0; ICU 74+ uses newer Unicode data.');
        }

        if (\PHP_VERSION_ID >= 80400 && '' === $source) {
            $this->expectException(\ValueError::class);
        }

        $options = \IDNA_CHECK_BIDI | \IDNA_CHECK_CONTEXTJ | \IDNA_USE_STD3_RULES | \IDNA_NONTRANSITIONAL_TO_ASCII;
        idn_to_ascii($source, $options, \INTL_IDNA_VARIANT_UTS46, $info);

        if (null === $info) {
            $this->markTestSkipped('PHP Bug #72506.');
        }

        if ([] === $toAsciiNStatus) {
            $this->assertSame($toAsciiN, $info['result']);
            $this->assertSame(0, $info['errors'], \sprintf('Expected no errors, but found %d.', $info['errors']));
        } else {
            $this->assertNotSame(0, $info['errors'], 'Expected to find errors, but found none.');
        }
    }

    /**
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
        if (\defined('INTL_ICU_VERSION') && version_compare(\INTL_ICU_VERSION, '74', '>=')) {
            $this->markTestSkipped('IdnaTestV2.txt is based on Unicode 13.0.0; ICU 74+ uses newer Unicode data.');
        }

        if (\PHP_VERSION_ID >= 80400 && '' === $source) {
            $this->expectException(\ValueError::class);
        }

        $options = \IDNA_CHECK_BIDI | \IDNA_CHECK_CONTEXTJ | \IDNA_USE_STD3_RULES;
        idn_to_ascii($source, $options, \INTL_IDNA_VARIANT_UTS46, $info);

        if (null === $info) {
            $this->markTestSkipped('PHP Bug #72506.');
        }

        if ([] === $toAsciiTStatus) {
            $this->assertSame($toAsciiT, $info['result']);
            $this->assertSame(0, $info['errors'], \sprintf('Expected no errors, but found %d.', $info['errors']));
        } else {
            $this->assertNotSame(0, $info['errors'], 'Expected to find errors, but found none.');
        }
    }

    /**
     * @requires PHP < 8
     *
     * @group legacy
     *
     * @dataProvider domainNamesProvider
     */
    public function testEncode2003($decoded, $encoded)
    {
        $result = @idn_to_ascii($decoded, \IDNA_DEFAULT, \INTL_IDNA_VARIANT_2003);
        $this->assertSame($encoded, $result);
    }

    /**
     * @dataProvider invalidUtf8DomainNamesProvider
     */
    public function testEncodeInvalid($decoded)
    {
        $result = idn_to_ascii($decoded, \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46);
        $this->assertFalse($result);
    }

    /**
     * @requires PHP < 8
     *
     * @group legacy
     *
     * @dataProvider domainNamesProvider
     */
    public function testDecode2003($decoded, $encoded)
    {
        $result = @idn_to_utf8($encoded, \IDNA_DEFAULT, \INTL_IDNA_VARIANT_2003);
        $this->assertSame($decoded, $result);
    }

    /**
     * @dataProvider domainNamesProvider
     */
    public function testEncodeUTS46($decoded, $encoded)
    {
        $result = idn_to_ascii($decoded, \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46);
        $this->assertSame($encoded, $result);
    }

    /**
     * @dataProvider domainNamesProvider
     */
    public function testDecodeUTS46($decoded, $encoded)
    {
        $result = idn_to_utf8($encoded, \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46);
        $this->assertSame($decoded, $result);
    }

    /**
     * @dataProvider domainNamesUppercaseUTS46Provider
     */
    public function testUppercaseUTS46($decoded, $ascii, $encoded)
    {
        $info = 123;
        $result = idn_to_ascii($decoded, \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46, $info);
        $this->assertSame($ascii, $result);

        $expected = [
            'result' => $result,
            'isTransitionalDifferent' => false,
            'errors' => 0,
        ];
        $this->assertSame($expected, $info);

        $info = 123;
        $result = idn_to_utf8($ascii, \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46, $info);
        $this->assertSame($encoded, $result);

        $expected = [
            'result' => $result,
            'isTransitionalDifferent' => false,
            'errors' => 0,
        ];
        $this->assertSame($expected, $info);
    }

    /**
     * @requires PHP < 8
     *
     * @group legacy
     *
     * @dataProvider domainNamesProvider
     */
    public function testEncodePhp53($decoded, $encoded)
    {
        $result = @Idn::idn_to_ascii($decoded, \IDNA_DEFAULT, \INTL_IDNA_VARIANT_2003);
        $this->assertSame($encoded, $result);
    }

    /**
     * IDNA 15.1.0 revision 31.
     *
     * This tests the change in "Section 4 Processing step 1. Map" which conditionally maps U+1E9E capital sharp s to
     * "ss" if Transitional_Processing is used.
     *
     * @dataProvider captialSharpSProvider
     */
    public function testCapitalSharpSProcessing($input, $expected, $flags)
    {
        idn_to_utf8($input, $flags, \INTL_IDNA_VARIANT_UTS46, $info);
        $this->assertSame($expected, $info['result']);
    }

    /**
     * IDNA 15.1.0 revision 31.
     *
     * This tests the additional validity check in "Section 4.1 Validity Criteria Processing step 4", which is used to
     * disallow labels that do not round trip.
     */
    public function testLabelsThatDoNotRoundTripAreDisallowed()
    {
        idn_to_utf8('xn--xn---epa.', \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46, $info1);
        idn_to_ascii($info1['result'], \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46, $info2);
        $this->assertSame(\IDNA_ERROR_PUNYCODE, \IDNA_ERROR_PUNYCODE & $info2['errors']);
    }

    /**
     * IDNA 15.1.0 revision 31.
     *
     * This tests the the additional condition in "Section 4 Processing step 4.1" where a label that starts with "xn--"
     * and contains a non-ASCII codepoint records an error and the processing steps continue with the next label.
     */
    public function testLabelStartingWithPunycodePrefixWithNonAsciiCharacterRecordsErrorAndIsSkipped()
    {
        idn_to_utf8('xn--🌈.xn--fa-hia.de', \IDNA_DEFAULT, \INTL_IDNA_VARIANT_UTS46, $info);
        $this->assertSame(\IDNA_ERROR_PUNYCODE, \IDNA_ERROR_PUNYCODE & $info['errors']);
        $this->assertSame('xn--🌈.faß.de', $info['result']);
    }

    public static function captialSharpSProvider()
    {
        return [
            ['Faß.de', 'fass.de', \IDNA_DEFAULT],
            ['Faß.de', 'faß.de', \IDNA_NONTRANSITIONAL_TO_UNICODE],
        ];
    }

    public static function domainNamesProvider()
    {
        return [
            [
                'foo.example.',
                'foo.example.',
            ],
            // https://en.wikipedia.org/wiki/IDN_Test_TLDs
            [
                'مثال.إختبار',
                'xn--mgbh0fb.xn--kgbechtv',
            ],
            [
                'مثال.آزمایشی',
                'xn--mgbh0fb.xn--hgbk6aj7f53bba',
            ],
            [
                '例子.测试',
                'xn--fsqu00a.xn--0zwm56d',
            ],
            [
                '例子.測試',
                'xn--fsqu00a.xn--g6w251d',
            ],
            [
                'пример.испытание',
                'xn--e1afmkfd.xn--80akhbyknj4f',
            ],
            [
                'उदाहरण.परीक्षा',
                'xn--p1b6ci4b4b3a.xn--11b5bs3a9aj6g',
            ],
            [
                'παράδειγμα.δοκιμή',
                'xn--hxajbheg2az3al.xn--jxalpdlp',
            ],
            [
                '실례.테스트',
                'xn--9n2bp8q.xn--9t4b11yi5a',
            ],
            [
                'בײַשפּיל.טעסט',
                'xn--fdbk5d8ap9b8a8d.xn--deba0ad',
            ],
            [
                '例え.テスト',
                'xn--r8jz45g.xn--zckzah',
            ],
            [
                'உதாரணம்.பரிட்சை',
                'xn--zkc6cc5bi7f6e.xn--hlcj6aya9esc7a',
            ],

            [
                'derhausüberwacher.de',
                'xn--derhausberwacher-pzb.de',
            ],
            [
                'renangonçalves.com',
                'xn--renangonalves-pgb.com',
            ],
            [
                'рф.ru',
                'xn--p1ai.ru',
            ],
            [
                'δοκιμή.gr',
                'xn--jxalpdlp.gr',
            ],
            [
                'ফাহাদ্১৯.বাংলা',
                'xn--65bj6btb5gwimc.xn--54b7fta0cc',
            ],
            [
                '𐌀𐌖𐌋𐌄𐌑𐌉·𐌌𐌄𐌕𐌄𐌋𐌉𐌑.gr',
                'xn--uba5533kmaba1adkfh6ch2cg.gr',
            ],
            [
                'guangdong.广东',
                'guangdong.xn--xhq521b',
            ],
            [
                'gwóźdź.pl',
                'xn--gwd-hna98db.pl',
            ],
            [
                'άέήίΰαβγδεζηθικλμνξοπρσστυφχ.com',
                'xn--hxacdefghijklmnopqrstuvw0caz0a1a2a.com',
            ],
            [
                'test@bücher.de',
                'xn--test@bcher-feb.de',
            ],
        ];
    }

    public static function domainNamesUppercaseUTS46Provider()
    {
        return [
            [
                'рф.RU',
                'xn--p1ai.ru',
                'рф.ru',
            ],
            [
                'GUANGDONG.广东',
                'guangdong.xn--xhq521b',
                'guangdong.广东',
            ],
            [
                'renanGonçalves.COM',
                'xn--renangonalves-pgb.com',
                'renangonçalves.com',
            ],
        ];
    }

    public static function invalidUtf8DomainNamesProvider()
    {
        return [
            [
                'äöüßáàăâåãąāæćĉčċçďđéèĕêěëėęēğĝġģĥħíìĭîïĩįīıĵķĺľļłńňñņŋóòŏôőõøōœĸŕřŗśŝšşťţŧúùŭûůűũųūŵýŷÿźžżðþ.de',
            ],
            [
                'aaaaa.aaaaaaaaaaaaaaa.aaaaaaaaaaaaaaaaaaa.aaaaaaaaaaaaaaaaa.aaaaaaaaaaaaaaaaaa.äöüßáàăâåãąāæćĉčċçďđéèĕêěëėęēğĝġģĥ.ħíìĭîïĩįīıĵķĺľļłńňñņŋóòŏôőõ.øōœĸŕřŗśŝšşťţŧúùŭûůűũųū.ŵýŷÿźžżðþ.de',
            ],
            [
                'aa..aa.de',
            ],
            [
                '-leading.de',
            ],
            [
                'trailing-.de',
            ],
        ];
    }

    /**
     * @param array<int, int|array<int, int>> $inherit
     *
     * @return array<int, int|array<int, int>>
     */
    private static function resolveErrorCodes($statusCodes, $inherit)
    {
        if ('' === $statusCodes) {
            return $inherit;
        }

        if ('[]' === $statusCodes) {
            return [];
        }

        $matchCount = preg_match_all('/[PVUABCX][0-9](?:_[0-9])?/', $statusCodes, $matches);

        if (\PREG_NO_ERROR !== preg_last_error()) {
            throw new \RuntimeException();
        }

        if (0 === $matchCount) {
            throw new \RuntimeException();
        }

        $errors = [];

        foreach ($matches[0] as $match) {
            if ('U' === $match[0]) {
                continue;
            }

            if (!isset(self::$ERROR_CODE_MAP[$match])) {
                throw new \RuntimeException(\sprintf('Unhandled error code %s.', $match));
            }

            $errors[] = self::$ERROR_CODE_MAP[$match];
        }

        return $errors;
    }
}
