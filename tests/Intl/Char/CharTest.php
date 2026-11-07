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

        $this->assertSame((new \ReflectionClass('IntlChar'))->getConstants(), $constants);
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
        foreach ([Char::PROPERTY_BIDI_CLASS, Char::PROPERTY_GENERAL_CATEGORY, Char::PROPERTY_GENERAL_CATEGORY_MASK, Char::PROPERTY_CANONICAL_COMBINING_CLASS, Char::PROPERTY_BIDI_PAIRED_BRACKET_TYPE] as $property) {
            yield 'getIntPropertyValue '.$property => ['getIntPropertyValue', [$property]];
        }
        foreach (require __DIR__.'/../../../src/Intl/Char/Resources/unidata/binaryProperties.php' as $property => $_) {
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
     * @requires extension intl
     */
    public function testStringsAreLikeIntl(): void
    {
        foreach (['a', 'A', 'é', 'א', '(', '٣', '五', '😀', 'ǅ', 'İ', "\u{10FFFF}", 'ab', '', "\xC0\x80", "\xED\xA0\x80", "\xF4\x90\x80\x80", "\xFF"] as $character) {
            foreach (['charDirection', 'charType', 'charMirror', 'tolower', 'toupper', 'totitle', 'foldCase', 'ord', 'isalpha', 'getNumericValue'] as $method) {
                $this->assertSame(@\IntlChar::$method($character), Char::$method($character), $method.' '.bin2hex($character));
            }
        }
    }
}
