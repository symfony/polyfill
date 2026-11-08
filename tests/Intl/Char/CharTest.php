<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Intl\Char;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Intl\Char\Char;

/**
 * @group class-polyfill
 *
 * @covers \Symfony\Polyfill\Intl\Char\Char::<!public>
 */
class CharTest extends TestCase
{
    public function testCharDirection(): void
    {
        $this->assertSame(Char::CHAR_DIRECTION_LEFT_TO_RIGHT, Char::charDirection('a'));
        $this->assertSame(Char::CHAR_DIRECTION_RIGHT_TO_LEFT, Char::charDirection('א'));
        $this->assertSame(Char::CHAR_DIRECTION_RIGHT_TO_LEFT_ARABIC, Char::charDirection(0x0627));
        $this->assertSame(Char::CHAR_DIRECTION_EUROPEAN_NUMBER, Char::charDirection('7'));
        $this->assertSame(Char::CHAR_DIRECTION_ARABIC_NUMBER, Char::charDirection(0x0663));
        $this->assertSame(Char::CHAR_DIRECTION_WHITE_SPACE_NEUTRAL, Char::charDirection(' '));
        $this->assertSame(Char::CHAR_DIRECTION_RIGHT_TO_LEFT_ISOLATE, Char::charDirection("\u{2067}"));
        $this->assertSame(Char::CHAR_DIRECTION_BOUNDARY_NEUTRAL, Char::charDirection(0));
        $this->assertSame(Char::CHAR_DIRECTION_RIGHT_TO_LEFT, Char::charDirection(0x05FF), 'An unassigned code point of the Hebrew block is right to left.');
        $this->assertSame(Char::CHAR_DIRECTION_BOUNDARY_NEUTRAL, Char::charDirection(0x10FFFF), 'A noncharacter is a boundary neutral.');
    }

    public function testInvalidCodePoints(): void
    {
        $this->assertNull(Char::charDirection('ab'));
        $this->assertNull(Char::charDirection(''));
        $this->assertNull(Char::charDirection("\xFF"));
        $this->assertNull(Char::charDirection(-1));
        $this->assertNull(Char::charDirection(0x110000));
        $this->assertNull(Char::charMirror('ab'));
        $this->assertNull(Char::ord('ab'));
        $this->assertNull(Char::chr(0x110000));
    }

    public function testMirrorsAndBrackets(): void
    {
        $this->assertSame(')', Char::charMirror('('));
        $this->assertSame(0x29, Char::charMirror(0x28));
        $this->assertSame('a', Char::charMirror('a'));
        $this->assertTrue(Char::isMirrored('<'));
        $this->assertFalse(Char::isMirrored('a'));
        $this->assertSame(']', Char::getBidiPairedBracket('['));
        $this->assertSame('a', Char::getBidiPairedBracket('a'));
        $this->assertSame(Char::BPT_OPEN, Char::getIntPropertyValue('(', Char::PROPERTY_BIDI_PAIRED_BRACKET_TYPE));
        $this->assertSame(Char::BPT_CLOSE, Char::getIntPropertyValue(')', Char::PROPERTY_BIDI_PAIRED_BRACKET_TYPE));
        $this->assertSame(Char::BPT_NONE, Char::getIntPropertyValue('<', Char::PROPERTY_BIDI_PAIRED_BRACKET_TYPE));
        $this->assertTrue(Char::hasBinaryProperty("\u{200F}", Char::PROPERTY_BIDI_CONTROL));
        $this->assertFalse(Char::hasBinaryProperty('a', Char::PROPERTY_BIDI_CONTROL));
    }

    public function testOrdAndChr(): void
    {
        $this->assertSame(0x1F600, Char::ord('😀'));
        $this->assertSame('😀', Char::chr(0x1F600));
        $this->assertSame('é', Char::chr('é'));
        $this->assertSame([17, 0, 0, 0], Char::getUnicodeVersion());
    }

    public function testOtherPropertiesAreNotProvided(): void
    {
        $this->expectException(\LogicException::class);

        Char::getIntPropertyValue('a', Char::PROPERTY_SCRIPT);
    }

    public function testEnumeratedProperties(): void
    {
        $this->assertSame(Char::EA_WIDE, Char::getIntPropertyValue('五', Char::PROPERTY_EAST_ASIAN_WIDTH));
        $this->assertSame(Char::LB_IDEOGRAPHIC, Char::getIntPropertyValue('五', Char::PROPERTY_LINE_BREAK));
        $this->assertSame(Char::JT_DUAL_JOINING, Char::getIntPropertyValue(0x0628, Char::PROPERTY_JOINING_TYPE));
        $this->assertSame(Char::GCB_REGIONAL_INDICATOR, Char::getIntPropertyValue(0x1F1EB, Char::PROPERTY_GRAPHEME_CLUSTER_BREAK));
        $this->assertSame(Char::WB_ALETTER, Char::getIntPropertyValue('a', Char::PROPERTY_WORD_BREAK));
        $this->assertSame(Char::HST_LV_SYLLABLE, Char::getIntPropertyValue('가', Char::PROPERTY_HANGUL_SYLLABLE_TYPE));
        $this->assertSame(230, Char::getIntPropertyValue('ǘ', Char::PROPERTY_TRAIL_CANONICAL_COMBINING_CLASS));
        $this->assertSame(0, Char::getIntPropertyValue('a', Char::PROPERTY_NUMERIC_VALUE), 'A property that has no integer values gives 0.');
        $this->assertSame(Char::LB_COUNT - 1, Char::getIntPropertyMaxValue(Char::PROPERTY_LINE_BREAK));
        $this->assertSame(-1, Char::getIntPropertyMaxValue(Char::PROPERTY_NAME));
    }

    public function testBinaryProperties(): void
    {
        $this->assertTrue(Char::hasBinaryProperty('😀', 57), 'Emoji');
        $this->assertTrue(Char::hasBinaryProperty('😀', 71), 'RGI_Emoji');
        $this->assertFalse(Char::hasBinaryProperty('a', 57), 'Emoji');
        $this->assertTrue(Char::hasBinaryProperty('a', Char::PROPERTY_NFD_INERT));
        $this->assertFalse(Char::hasBinaryProperty('a', Char::PROPERTY_NFC_INERT), 'a composes with a following accent.');
        $this->assertTrue(Char::hasBinaryProperty('$', Char::PROPERTY_NFC_INERT));
        $this->assertFalse(Char::hasBinaryProperty("\u{0301}", Char::PROPERTY_SEGMENT_STARTER));
        $this->assertTrue(Char::hasBinaryProperty('A', Char::PROPERTY_CASE_SENSITIVE));
        $this->assertTrue(Char::hasBinaryProperty("\t", Char::PROPERTY_POSIX_BLANK));
        $this->assertFalse(Char::hasBinaryProperty('a', -1));
        $this->assertFalse(Char::hasBinaryProperty('a', Char::PROPERTY_BINARY_LIMIT));
        $this->assertNull(Char::hasBinaryProperty('ab', Char::PROPERTY_BINARY_LIMIT));
    }

    public function testScalarsAreIntegers(): void
    {
        $this->assertSame(Char::CHAR_CATEGORY_UPPERCASE_LETTER, Char::charType(65.0));
        $this->assertSame(97, Char::tolower(65.0));
        $this->assertSame(97, Char::tolower(97.0));
        $this->assertSame(1, Char::tolower(true));
        $this->assertSame(0, Char::ord(null));
        $this->assertSame("\0", Char::chr(false));
        $this->assertNull(Char::charType('65'));
    }

    public function testEnumCharTypes(): void
    {
        $ranges = [];
        Char::enumCharTypes(static function (int $start, int $end, int $type) use (&$ranges) {
            $ranges[] = [$start, $end, $type];
        });

        $this->assertSame([0, 32, Char::CHAR_CATEGORY_CONTROL_CHAR], $ranges[0]);
        $this->assertSame([32, 33, Char::CHAR_CATEGORY_SPACE_SEPARATOR], $ranges[1]);
        $this->assertSame(0x110000, $ranges[\count($ranges) - 1][1]);
        for ($i = 1; $i < \count($ranges); ++$i) {
            $this->assertSame($ranges[$i - 1][1], $ranges[$i][0]);
            $this->assertNotSame($ranges[$i - 1][2], $ranges[$i][2]);
        }
    }

    /**
     * @requires extension intl
     */
    public function testConstantsAreThoseOfIntl(): void
    {
        $constants = [];
        foreach ((new \ReflectionClass(Char::class))->getReflectionConstants() as $constant) {
            if ($constant->isPublic()) {
                $constants[$constant->getName()] = $constant->getValue();
            }
        }
        $expected = (new \ReflectionClass('IntlChar'))->getConstants();
        ksort($constants);
        ksort($expected);

        if (\IntlChar::getUnicodeVersion() === Char::getUnicodeVersion()) {
            $this->assertSame($expected, $constants);

            return;
        }

        // The version of Unicode, the counts and the limits change with the version of ICU, and its new constants too
        foreach ($expected as $name => $value) {
            $this->assertArrayHasKey($name, $constants);
            if ('UNICODE_VERSION' !== $name && !preg_match('/_(COUNT|LIMIT)$/', $name)) {
                $this->assertSame($value, $constants[$name], $name);
            }
        }
    }

    /**
     * @return iterable<string, array{string, array}>
     */
    public static function provideMethods(): iterable
    {
        foreach (['charDirection', 'charType', 'getCombiningClass', 'charMirror', 'getBidiPairedBracket', 'tolower', 'toupper', 'totitle', 'foldCase', 'isMirrored', 'isUAlphabetic', 'isULowercase', 'isUUppercase', 'isUWhiteSpace', 'isalpha', 'islower', 'isupper', 'istitle', 'isdigit', 'isalnum', 'ispunct', 'isdefined', 'isbase', 'iscntrl', 'isprint', 'isgraph', 'isJavaSpaceChar', 'isIDStart', 'isJavaIDStart', 'isIDPart', 'isJavaIDPart', 'isIDIgnorable', 'isISOControl', 'isspace', 'isWhitespace', 'isblank', 'isxdigit', 'charDigitValue', 'digit', 'getNumericValue', 'charAge'] as $method) {
            yield $method => [$method, []];
        }
        yield 'foldCase, Turkic' => ['foldCase', [Char::FOLD_CASE_EXCLUDE_SPECIAL_I]];
        yield 'digit, base 36' => ['digit', [36]];
        // All the integer properties, but the blocks and the scripts
        foreach (array_merge(range(Char::PROPERTY_INT_START, 4122), [Char::PROPERTY_GENERAL_CATEGORY_MASK]) as $property) {
            if (Char::PROPERTY_BLOCK !== $property && Char::PROPERTY_SCRIPT !== $property) {
                yield 'getIntPropertyValue '.$property => ['getIntPropertyValue', [$property]];
            }
        }
        for ($property = Char::PROPERTY_BINARY_START; $property < Char::PROPERTY_BINARY_LIMIT; ++$property) {
            yield 'hasBinaryProperty '.$property => ['hasBinaryProperty', [$property]];
        }
    }

    /**
     * Every code point, like intl.
     *
     * @requires extension intl
     *
     * @dataProvider provideMethods
     */
    public function testEveryCodePointIsLikeIntl(string $method, array $arguments): void
    {
        if (\IntlChar::getUnicodeVersion() !== Char::getUnicodeVersion()) {
            $this->markTestSkipped(\sprintf('Intl has the data of Unicode %s.', implode('.', \IntlChar::getUnicodeVersion())));
        }

        $differences = [];
        for ($codePoint = 0; $codePoint <= 0x10FFFF; ++$codePoint) {
            if (($expected = \IntlChar::$method($codePoint, ...$arguments)) !== ($actual = Char::$method($codePoint, ...$arguments))) {
                $differences[] = \sprintf('U+%04X: %s, not %s', $codePoint, json_encode($expected), json_encode($actual));
                if (\count($differences) >= 10) {
                    break;
                }
            }
        }

        $this->assertSame([], $differences);
    }

    /**
     * Every property, like intl.
     *
     * @requires extension intl
     */
    public function testEveryPropertyIsLikeIntl(): void
    {
        if (\IntlChar::getUnicodeVersion() !== Char::getUnicodeVersion()) {
            $this->markTestSkipped(\sprintf('Intl has the data of Unicode %s.', implode('.', \IntlChar::getUnicodeVersion())));
        }

        $differences = [];
        for ($property = -1; $property <= Char::PROPERTY_OTHER_PROPERTY_LIMIT; ++$property) {
            if (Char::PROPERTY_BLOCK === $property || Char::PROPERTY_SCRIPT === $property) {
                continue;
            }
            foreach (['getIntPropertyMinValue' => [$property], 'getIntPropertyMaxValue' => [$property], 'getIntPropertyValue' => ['a', $property], 'hasBinaryProperty' => ['a', $property]] as $method => $arguments) {
                if (($expected = \IntlChar::$method(...$arguments)) !== ($actual = Char::$method(...$arguments))) {
                    $differences[] = \sprintf('%s(%d): %s, not %s', $method, $property, json_encode($expected), json_encode($actual));
                }
            }
        }

        $this->assertSame([], \array_slice($differences, 0, 10));
    }

    /**
     * @requires extension intl
     */
    public function testEnumCharTypesIsLikeIntl(): void
    {
        if (\IntlChar::getUnicodeVersion() !== Char::getUnicodeVersion()) {
            $this->markTestSkipped(\sprintf('Intl has the data of Unicode %s.', implode('.', \IntlChar::getUnicodeVersion())));
        }

        $expected = $actual = [];
        \IntlChar::enumCharTypes(static function (int $start, int $end, int $type) use (&$expected) {
            $expected[] = [$start, $end, $type];
        });
        Char::enumCharTypes(static function (int $start, int $end, int $type) use (&$actual) {
            $actual[] = [$start, $end, $type];
        });

        $this->assertSame($expected, $actual);
    }

    /**
     * @requires extension intl
     */
    public function testScalarsAreLikeIntl(): void
    {
        foreach ([65.0, 0.0, 1114111.0, true, false] as $value) {
            foreach (['charType', 'tolower', 'chr', 'ord', 'isalpha', 'charAge'] as $method) {
                $this->assertSame(\IntlChar::$method($value), Char::$method($value), $method.' '.var_export($value, true));
            }
        }
    }

    /**
     * @requires extension intl
     */
    public function testStringsAreLikeIntl(): void
    {
        foreach (['a', 'A', 'é', 'א', '(', '٣', '五', '😀', 'ǅ', 'İ', "\u{10FFFF}", 'ab', '', "\xC0\x80", "\xED\xA0\x80", "\xF4\x90\x80\x80", "\xFF"] as $character) {
            foreach (['charDirection', 'charType', 'charMirror', 'tolower', 'toupper', 'totitle', 'foldCase', 'ord', 'isalpha', 'getNumericValue'] as $method) {
                // intl < 8.2 requires the options of foldCase()
                $arguments = 'foldCase' === $method ? [$character, Char::FOLD_CASE_DEFAULT] : [$character];
                $this->assertSame(@\IntlChar::$method(...$arguments), Char::$method(...$arguments), $method.' '.bin2hex($character));
            }
        }
    }
}
