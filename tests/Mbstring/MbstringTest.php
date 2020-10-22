<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Mbstring;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Mbstring\Mbstring as p;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @covers \Symfony\Polyfill\Mbstring\Mbstring::<!public>
 */
class MbstringTest extends TestCase
{
    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_internal_encoding
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_list_encodings
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_substitute_character
     */
    public function testStubs()
    {
        $this->assertFalse(@mb_substitute_character('?'));
        $this->assertTrue(mb_substitute_character('none'));
        $this->assertSame('none', mb_substitute_character());

        $this->assertContains('UTF-8', mb_list_encodings());

        $this->assertTrue(mb_internal_encoding('utf8'));
        $this->assertFalse(@mb_internal_encoding('no-no'));
        $this->assertSame('UTF-8', mb_internal_encoding());
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_convert_encoding
     */
    public function testConvertEncoding()
    {
        $this->assertSame(utf8_decode('déjà'), mb_convert_encoding('déjà', 'Windows-1252'));
        $this->assertSame(base64_encode('déjà'), mb_convert_encoding('déjà', 'Base64'));
        $this->assertSame('&#23455;<&>d&eacute;j&agrave;', mb_convert_encoding('実<&>déjà', 'Html-entities'));
        $this->assertSame('déjà', mb_convert_encoding(base64_encode('déjà'), 'Utf-8', 'Base64'));
        $this->assertSame('déjà', mb_convert_encoding('d&eacute;j&#224;', 'Utf-8', 'Html-entities'));
        $this->assertSame('déjà', mb_convert_encoding(utf8_decode('déjà'), 'Utf-8', 'ASCII,ISO-2022-JP,UTF-8,ISO-8859-1'));
        $this->assertSame('déjà', mb_convert_encoding(utf8_decode('déjà'), 'Utf-8', array('ASCII', 'ISO-2022-JP', 'UTF-8', 'ISO-8859-1')));
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_decode_numericentity
     */
    public function testDecodeNumericEntity()
    {
        $convmap = array(0x80, 0x10ffff, 0x1, 0x1fffff);
        $this->assertNull(@mb_decode_numericentity(new \stdClass(), $convmap, 'UTF-8'));
        $this->assertFalse(@mb_decode_numericentity('déjà', new \stdClass(), 'UTF-8'));
        $this->assertFalse(@mb_decode_numericentity('déjà', array(), 'UTF-8'));
        $this->assertEmpty(@mb_decode_numericentity('déjà', $convmap, new \stdClass()));  // PHPUnit returns null.

        $this->assertSame('', mb_decode_numericentity('', $convmap, 'UTF-8'));
        $iso = 'déjà &amp; &225; &#E1; &#XE1; &#e1; &#Xe1;';
        $this->assertSame($iso, mb_decode_numericentity($iso, $convmap, 'UTF-8'));

        $this->assertSame('déjà &#0; à á', mb_decode_numericentity('déjà &#0; &#225; &#226;', $convmap, 'UTF-8'));
        $this->assertSame('déjà &#0; à á', mb_decode_numericentity('déjà &#0; &#0000225; &#0000226;', $convmap, 'UTF-8'));
        if (PHP_VERSION > 54000) {
            $this->assertSame('déjà &#0; à á', mb_decode_numericentity('déjà &#0; &#xe1; &#xe2;', $convmap, 'UTF-8'));
            $this->assertSame('déjà &#0; à á', mb_decode_numericentity('déjà &#0; &#x0000e1; &#x0000e2;', $convmap, 'UTF-8'));
            $this->assertSame('déjà &#0; à á', mb_decode_numericentity('déjà &#0; &#xE1; &#xE2;', $convmap, 'UTF-8'));
            $this->assertSame('déjà &#0; à á', mb_decode_numericentity('déjà &#0; &#x0000E1; &#x0000E2;', $convmap, 'UTF-8'));
        }
        --$convmap[2];
        $this->assertSame('déjà &#0; á â', mb_decode_numericentity('déjà &#0; &#225; &#226;', $convmap, 'UTF-8'));
        --$convmap[2];
        $this->assertSame('déjà &#0; â ã', mb_decode_numericentity('déjà &#0; &#225; &#226;', $convmap, 'UTF-8'));

        $bogusDecEntities = 'déjà &#0; &#225;&#225; &#&#225&#225 &#225 &#225t';
        $this->assertSame('déjà &#0; ââ &#&#225â â ât', mb_decode_numericentity($bogusDecEntities, $convmap, 'UTF-8'));

        if (PHP_VERSION > 54000) {
            $bogusHexEntities = 'déjà &#x0; &#xe1;&#xe1; &#xe1 &#xe1t &#xE1 &#xE1t';
            $this->assertSame('déjà &#x0; ââ â ât â ât', mb_decode_numericentity($bogusHexEntities, $convmap, 'UTF-8'));
        }

        array_push($convmap, 0x1f600, 0x1f64f, -0x1f602, 0x0);
        $this->assertSame('déjà 😂 â ã', mb_decode_numericentity('déjà &#0; &#225; &#226;', $convmap, 'UTF-8'));

        $convmap = array(0x100, 0x10ffff, 0x0, 0x1fffff);
        $this->assertSame("\xFE", mb_decode_numericentity('&#351;', $convmap, 'ISO-8859-9'));
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_decode_numericentity
     */
    public function testDecodeNumericEntityWarnsOnInvalidInputType()
    {
        $this->setExpectedException('PHPUnit\Framework\Error\Warning', 'expects parameter 1 to be string');
        mb_decode_numericentity(new \stdClass(), array(0x0, 0x10ffff, 0x0, 0x1fffff), 'UTF-8');
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_decode_numericentity
     */
    public function testDecodeNumericEntityWarnsOnInvalidEncodingType()
    {
        $this->setExpectedException('PHPUnit\Framework\Error\Warning', 'expects parameter 3 to be string');
        mb_decode_numericentity('déjà', array(0x0, 0x10ffff, 0x0, 0x1fffff), new \stdClass());
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_encode_numericentity
     */
    public function testEncodeNumericEntity()
    {
        $convmap = array(0x80, 0x10ffff, 0x1, 0x1fffff);
        $this->assertNull(@mb_encode_numericentity(new \stdClass(), $convmap, 'UTF-8'));
        $this->assertFalse(@mb_encode_numericentity('déjà', new \stdClass(), 'UTF-8'));
        $this->assertFalse(@mb_encode_numericentity('déjà', array(), 'UTF-8'));
        $this->assertNull(@mb_encode_numericentity('déjà', $convmap, new \stdClass()));
        $this->assertNull(@mb_encode_numericentity('déjà', $convmap, 'UTF-8', new \stdClass()));

        $this->assertSame('', mb_encode_numericentity('', $convmap, 'UTF-8'));
        $iso = 'abc &amp; &#225; &#xe1; &#xE1;';
        $this->assertSame($iso, mb_encode_numericentity($iso, $convmap, 'UTF-8'));

        $convmap[0] = 0x21;
        $this->assertSame('&#98; &#225; &#23456; &#128515;', mb_encode_numericentity('a à 実 😂', $convmap, 'UTF-8'));
        --$convmap[2];
        $this->assertSame('&#97; &#224; &#23455; &#128514;', mb_encode_numericentity('a à 実 😂', $convmap, 'UTF-8'));
        --$convmap[2];
        $this->assertSame('&#96; &#223; &#23454; &#128513;', mb_encode_numericentity('a à 実 😂', $convmap, 'UTF-8'));

        array_push($convmap, 0x0, 0x1f, 0x1f602, 0x1fffff);
        $this->assertSame('&#128514; &#96;', mb_encode_numericentity("\x00 a", $convmap, 'UTF-8'));

        $convmap = array(0x100, 0x10ffff, 0x0, 0x1fffff);
        $this->assertSame('&#351;', mb_encode_numericentity("\xFE", $convmap, 'ISO-8859-9'));

        if (PHP_VERSION > 54000) {
            $this->assertSame('&#351;', mb_encode_numericentity("\xFE", $convmap, 'ISO-8859-9', false));
            $this->assertSame('&#x15F;', mb_encode_numericentity("\xFE", $convmap, 'ISO-8859-9', true));
        }

        // U+1F602 FACE WITH TEARS OF JOY is F0 9F 98 82 in UTF-8. ISO-8859-9 leaves 7F-9F undefined.
        $this->assertSame("abc &#287;\x9F\x98\x82", mb_encode_numericentity('abc 😂', $convmap, 'ISO-8859-9'));
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_decode_numericentity
     */
    public function testEncodeNumericEntityWarnsOnInvalidInputType()
    {
        $this->setExpectedException('PHPUnit\Framework\Error\Warning', 'expects parameter 1 to be string');
        mb_encode_numericentity(new \stdClass(), array(0x0, 0x10ffff, 0x0, 0x1fffff), 'UTF-8');
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_decode_numericentity
     */
    public function testEncodeNumericEntityWarnsOnInvalidEncodingType()
    {
        $this->setExpectedException('PHPUnit\Framework\Error\Warning', 'expects parameter 3 to be string');
        mb_encode_numericentity('déjà', array(0x0, 0x10ffff, 0x0, 0x1fffff), new \stdClass());
    }

    /**
     * @requires PHP 5.4
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_decode_numericentity
     */
    public function testEncodeNumericEntityWarnsOnInvalidIsHexType()
    {
        $this->setExpectedException('PHPUnit\Framework\Error\Warning', 'expects parameter 4 to be bool');
        mb_encode_numericentity('déjà', array(0x0, 0x10ffff, 0x0, 0x1fffff), 'UTF-8', new \stdClass());
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_strtolower
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_strtoupper
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_convert_case
     */
    public function testStrCase()
    {
        $this->assertSame(\PHP_VERSION_ID >= 70300 ? 'i̇' : 'i', \mb_strtolower('İ'));
        $this->assertSame('déjà σσς iiıi', p::mb_strtolower('DÉJÀ Σσς İIıi'));
        $this->assertSame('DÉJÀ ΣΣΣ İIII', mb_strtoupper('Déjà Σσς İIıi'));
        if (PCRE_VERSION >= '8.10') {
            $this->assertSame('Déjà Σσσ Iı Ii İi', p::mb_convert_case('DÉJÀ ΣΣΣ ıı iI İİ', MB_CASE_TITLE));
        }
        if (\PHP_VERSION_ID >= 70000) {
            // Native iconv() is buggy before PHP 7
            $this->assertSame('ab', str_replace('?', '', mb_strtolower(urldecode('a%A1%C0b'))));
        }
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_convert_case
     */
    public function testTitleCase()
    {
        for ($i = 1; $i < 127; ++$i) {
            switch (\chr($i)) {
                case '!':
                case '"':
                case '#':
                case '%':
                case '&':
                case '*':
                case ',':
                case '/':
                case ';':
                case '?':
                case '@':
                case '\\':
                    if (\PHP_VERSION_ID < 70300) {
                        continue 2;
                    }
            }
            $this->assertSame(mb_convert_case('a'.\chr($i).'b', MB_CASE_TITLE, 'UTF-8'), p::mb_convert_case('a'.\chr($i).'b', MB_CASE_TITLE, 'UTF-8'), 'Title case for char 0x'.dechex($i));
        }
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_strlen
     */
    public function testStrlen()
    {
        $this->assertSame(2, mb_strlen("\x00\xFF", 'ASCII'));
        $this->assertSame(2, mb_strlen("\x00\xFF", 'CP850'));
        $this->assertSame(3, mb_strlen('한국어'));
        $this->assertSame(8, mb_strlen(\Normalizer::normalize('한국어', \Normalizer::NFD)));
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_substr
     */
    public function testSubstr()
    {
        $c = 'déjà';

        $this->assertSame('jà', mb_substr($c, 2));
        $this->assertSame('jà', mb_substr($c, -2));
        $this->assertSame('jà', mb_substr($c, -2, 3));
        $this->assertSame('', mb_substr($c, -1, 0));
        $this->assertSame('', mb_substr($c, 1, -4));
        $this->assertSame('j', mb_substr($c, -2, -1));
        $this->assertSame('', mb_substr($c, -2, -2));
        $this->assertSame('', mb_substr($c, 5, 0));
        $this->assertSame('', mb_substr($c, -5, 0));

        $this->assertSame("\xFF", mb_substr("\x00\xFF", -1, 1, 'ASCII'));
        $this->assertSame("\x00", mb_substr("\x00\xFF", 0, 1, 'ASCII'));
        $this->assertSame("\x00\xFF", mb_substr("\x00\xFF", 0, 2, 'ASCII'));
        $this->assertSame('', mb_substr("\x00\xFF", 2, 1, 'ASCII'));
        $this->assertSame('', mb_substr("\x00\xFF", 3, 1, 'ASCII'));
        $this->assertSame("\xFF", mb_substr("\x00\xFF", -1, 1, 'CP850'));
        $this->assertSame("\x00", mb_substr("\x00\xFF", 0, 1, 'CP850'));
        $this->assertSame("\x00\xFF", mb_substr("\x00\xFF", 0, 2, 'CP850'));
        $this->assertSame('', mb_substr("\x00\xFF", 2, 1, 'CP850'));
        $this->assertSame('', mb_substr("\x00\xFF", 3, 1, 'CP850'));
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_strpos
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_stripos
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_strrpos
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_strripos
     */
    public function testStrpos()
    {
        $this->assertFalse(@mb_strpos('abc', ''));
        $this->assertFalse(@mb_strpos('abc', 'a', -1));
        $this->assertFalse(mb_strpos('abc', 'd'));
        $this->assertFalse(mb_strpos('abc', 'a', 3));
        $this->assertSame(1, mb_strpos('한국어', '국'));
        $this->assertSame(3, mb_stripos('DÉJÀ', 'à'));
        $this->assertFalse(mb_strrpos('한국어', ''));
        $this->assertSame(1, mb_strrpos('한국어', '국'));
        $this->assertSame(3, mb_strripos('DÉJÀ', 'à'));
        $this->assertSame(1, mb_stripos('aςσb', 'ΣΣ'));
        $this->assertSame(1, mb_strripos('aςσb', 'ΣΣ'));
        $this->assertSame(3, mb_strrpos('ababab', 'b', -2));
        $this->assertSame(3, mb_strrpos('ababab', 'b', -3));
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_strpos
     */
    public function testStrposEmptyDelimiter()
    {
        mb_strpos('abc', 'a');
        $this->setExpectedException('PHPUnit\Framework\Error\Warning', 'Empty delimiter');
        mb_strpos('abc', '');
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_strpos
     */
    public function testStrposNegativeOffset()
    {
        mb_strpos('abc', 'a');
        if (\PHP_VERSION_ID >= 70100) {
            $this->assertFalse(mb_strpos('abc', 'a', -1));
        } else {
            $this->setExpectedException('PHPUnit\Framework\Error\Warning', 'Offset not contained in string');
            mb_strpos('abc', 'a', -1);
        }
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_str_split
     */
    public function testStrSplit()
    {
        $this->assertSame(array('H', "\r", "\n", 'W'), mb_str_split("H\r\nW", 1));
        $this->assertSame(array('Hell', "o\nWo", 'rld!'), mb_str_split("Hello\nWorld!", 4));
        $this->assertSame(array('한', '국', '어'), mb_str_split('한국어'));
        $this->assertSame(array('по', 'бе', 'да'), mb_str_split('победа', 2));
        $this->assertSame(array('źre', 'bię'), mb_str_split('źrebię', 3));
        $this->assertSame(array('źr', 'ebi', 'ę'), mb_str_split('źrebię', 3, 'ASCII'));
        $this->assertSame(array('alpha', 'bet'), mb_str_split('alphabet', 5));
        $this->assertSame(array('e', '́', '💩', '𐍈'), mb_str_split('é💩𐍈', 1, 'UTF-8'));
        $this->assertSame(array(), mb_str_split('', 1, 'UTF-8'));
        $this->assertFalse(@mb_str_split('победа', 0));
        $this->assertNull(@mb_str_split(array(), 0));

        $this->setExpectedException('PHPUnit\Framework\Error\Warning', 'The length of each segment must be greater than zero');
        mb_str_split('победа', 0);
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_strstr
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_stristr
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_strrchr
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_strrichr
     */
    public function testStrstr()
    {
        $this->assertSame('국어', mb_strstr('한국어', '국'));
        $this->assertSame('ÉJÀ', mb_stristr('DÉJÀ', 'é'));

        $this->assertSame('éjàdéjà', mb_strstr('déjàdéjà', 'é'));
        $this->assertSame('ÉJÀDÉJÀ', mb_stristr('DÉJÀDÉJÀ', 'é'));
        $this->assertSame('ςσb', mb_stristr('aςσb', 'ΣΣ'));
        $this->assertSame('éjà', mb_strrchr('déjàdéjà', 'é'));
        $this->assertSame('éjà', mb_strrchr('déjàdéjà', 'é', false, 'ASCII'));
        $this->assertFalse(mb_strrchr('déjàdéjà', 'X', false, 'ASCII'));
        $this->assertSame('ÉJÀ', mb_strrichr('DÉJÀDÉJÀ', 'é'));

        $this->assertSame('d', mb_strstr('déjàdéjà', 'é', true));
        $this->assertSame('D', mb_stristr('DÉJÀDÉJÀ', 'é', true));
        $this->assertSame('a', mb_stristr('aςσb', 'ΣΣ', true));
        $this->assertSame('déjàd', mb_strrchr('déjàdéjà', 'é', true));
        $this->assertSame('déjàd', mb_strrchr('déjàdéjà', 'é', true, 'ASCII'));
        $this->assertFalse(mb_strrchr('déjàdéjà', 'X', true, 'ASCII'));
        $this->assertSame('DÉJÀD', mb_strrichr('DÉJÀDÉJÀ', 'é', true));
        $this->assertSame('Paris', mb_stristr('der Straße nach Paris', 'Paris'));
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_check_encoding
     */
    public function testCheckEncoding()
    {
        $this->assertFalse(p::mb_check_encoding());
        $this->assertTrue(mb_check_encoding('aςσb', 'UTF8'));
        $this->assertTrue(mb_check_encoding('abc', 'ASCII'));
        $this->assertTrue(mb_check_encoding("\xE9", 'Windows-1252'));
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_detect_encoding
     */
    public function testDetectEncoding()
    {
        $this->assertTrue(mb_detect_order('ASCII, UTF-8'));
        $this->assertSame('ASCII', mb_detect_encoding('abc'));
        $this->assertSame('UTF-8', mb_detect_encoding('abc', 'UTF8, ASCII'));
        $this->assertSame('ISO-8859-1', mb_detect_encoding("\xE9", array('UTF-8', 'ASCII', 'ISO-8859-1'), true));
        $this->assertFalse(mb_detect_encoding("\xE9", array('UTF-8', 'ASCII', 'Windows-1252'), true));
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_detect_order
     */
    public function testDetectOrder()
    {
        $this->assertTrue(mb_detect_order('ASCII, UTF-8'));
        $this->assertSame(array('ASCII', 'UTF-8'), mb_detect_order());
        $this->assertTrue(mb_detect_order(array('ASCII', 'UTF-8')));
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_language
     */
    public function testLanguage()
    {
        $this->assertTrue(mb_language('UNI'));
        $this->assertSame('uni', mb_language());
        $this->assertFalse(@mb_language('ABC'));
        $this->assertTrue(mb_language('neutral'));
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_encoding_aliases
     */
    public function testEncodingAliases()
    {
        $this->assertSame(array('utf8'), mb_encoding_aliases('UTF-8'));
        $this->assertFalse(p::mb_encoding_aliases('ASCII'));
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_strwidth
     */
    public function testStrwidth()
    {
        $this->assertSame(3, mb_strwidth("\000実", 'UTF-8'));
        $this->assertSame(4, mb_strwidth('déjà', 'UTF-8'));
        $this->assertSame(4, mb_strwidth(utf8_decode('déjà'), 'CP1252'));
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_chr
     */
    public function testChr()
    {
        $this->assertSame("\xF0\xA0\xAE\xB7", mb_chr(0x20BB7));
        $this->assertSame("\xE9", mb_chr(0xE9, 'CP1252'));
    }

    /**
     * @covers \Symfony\Polyfill\Mbstring\Mbstring::mb_ord
     */
    public function testOrd()
    {
        $this->assertSame(0x20BB7, mb_ord("\xF0\xA0\xAE\xB7"));
        $this->assertSame(0xE9, mb_ord("\xE9", 'CP1252'));
    }

    public function testScrub()
    {
        $subst = \mb_substitute_character();
        \mb_substitute_character('none');
        $this->assertSame('ab', mb_scrub("a\xE9b"));
        \mb_substitute_character($subst);
    }

    public function setExpectedException($exception, $message = '', $code = null)
    {
        if (!class_exists('PHPUnit\Framework\Error\Notice')) {
            $exception = str_replace('PHPUnit\\Framework\\Error\\', 'PHPUnit_Framework_Error_', $exception);
        }
        if (method_exists($this, 'expectException')) {
            $this->expectException($exception);
            $this->expectExceptionMessage($message);
        } else {
            parent::setExpectedException($exception, $message, $code);
        }
    }
}
