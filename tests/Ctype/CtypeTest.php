<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Ctype;

use PHPUnit\Framework\TestCase;

/**
 * @group legacy
 */
class CtypeTest extends TestCase
{
    /**
     * @dataProvider provideValidAlnums
     */
    public function testValidCtypeAlnum($text)
    {
        $this->assertTrue(ctype_alnum($text));
    }

    public static function provideValidAlnums()
    {
        return [
            ['0'],
            [53],
            [65],
            [98],
            ['asdf'],
            ['ADD'],
            ['123'],
            ['A1cbad'],
            [280],
        ];
    }

    /**
     * @dataProvider provideInvalidAlnum
     */
    public function testInvalidCtypeAlnum($text)
    {
        $this->assertFalse(ctype_alnum($text));
    }

    public static function provideInvalidAlnum()
    {
        return [
            [[]],
            [true],
            [null],
            [new \stdClass()],
            [53.0],
            [25.4],
            [-129],
            [-386],
            [8],
            [43],
            [-127],
            ['asd df'],
            [''],
            ['é'],
            ['!!'],
            ['!asdf'],
            ['as2!a'],
            ["\x00asdf"],
        ];
    }

    /**
     * @dataProvider provideValidAlphas
     */
    public function testValidCtypeAlpha($text)
    {
        $this->assertTrue(ctype_alpha($text));
    }

    public static function provideValidAlphas()
    {
        return [
            [65],
            [98],
            ['asdf'],
            ['ADD'],
            ['bAcbad'],
        ];
    }

    /**
     * @dataProvider provideInvalidAlpha
     */
    public function testInvalidCtypeAlpha($text)
    {
        $this->assertFalse(ctype_alpha($text));
    }

    public static function provideInvalidAlpha()
    {
        return [
            [[]],
            [true],
            [null],
            [new \stdClass()],
            [53.0],
            [25.4],
            [-129],
            [-386],
            [8],
            [43],
            [53],
            ['asd df'],
            [''],
            ['é'],
            ['1234'],
            ['13addfadsf2'],
            ["\x00asd"],
            [280],
        ];
    }

    /**
     * @dataProvider provideValidCntrls
     */
    public function testValidCtypeCntrl($text)
    {
        $this->assertTrue(ctype_cntrl($text));
    }

    public static function provideValidCntrls()
    {
        return [
            [8],
            [127],
            ["\x00"],
            ["\x02"],
            [\chr(127)],
        ];
    }

    /**
     * @dataProvider provideInvalidCntrl
     */
    public function testInvalidCtypeCntrl($text)
    {
        $this->assertFalse(ctype_cntrl($text));
    }

    public static function provideInvalidCntrl()
    {
        return [
            [[]],
            [true],
            [null],
            [new \stdClass()],
            [53.0],
            [25.4],
            [-129],
            [-386],
            [53],
            [65],
            [98],
            [43],
            [280],
            ['asd df'],
            [''],
            ['é'],
            ['1234'],
            ['13addfadsf2'],
            ["\x00adf"],
            [\chr(127).'adf'],
        ];
    }

    /**
     * @dataProvider provideValidDigits
     */
    public function testValidCtypeDigit($text)
    {
        $this->assertTrue(ctype_digit($text));
    }

    public static function provideValidDigits()
    {
        return [
            ['0'],
            [53],
            [280],
            ['123'],
            ['01234'],
            ['934'],
        ];
    }

    /**
     * @dataProvider provideInvalidDigit
     */
    public function testInvalidCtypeDigit($text)
    {
        $this->assertFalse(ctype_digit($text));
    }

    public static function provideInvalidDigit()
    {
        return [
            [[]],
            [true],
            [null],
            [new \stdClass()],
            [53.0],
            [25.4],
            [-129],
            [-386],
            [8],
            [43],
            [65],
            [98],
            [-129],
            [-456],
            ['asd df'],
            [''],
            ['é'],
            ['1234B'],
            ['13addfadsf2'],
            ["\x00a"],
            [\chr(127), '-3', '3.5'],
        ];
    }

    /**
     * @dataProvider provideValidGraphs
     */
    public function testValidCtypeGraph($text)
    {
        $this->assertTrue(ctype_graph($text));
    }

    public static function provideValidGraphs()
    {
        return [
            [-129],
            [-386],
            ['0'],
            [43],
            [53],
            [65],
            [98],
            ['asdf'],
            ['ADD'],
            ['123'],
            ['A1cbad'],
            ['!!'],
            ['!asdF'],
        ];
    }

    /**
     * @dataProvider provideInvalidGraph
     */
    public function testInvalidCtypeGraph($text)
    {
        $this->assertFalse(ctype_graph($text));
    }

    public static function provideInvalidGraph()
    {
        return [
            [[]],
            [true],
            [null],
            [new \stdClass()],
            [53.0],
            [25.4],
            [8],
            [127],
            ['asd df'],
            [''],
            ['é'],
            ["\n"],
            ["\x00asdf"],
        ];
    }

    /**
     * @dataProvider provideValidLowers
     */
    public function testValidCtypeLower($text)
    {
        $this->assertTrue(ctype_lower($text));
    }

    public static function provideValidLowers()
    {
        return [
            [98],
            ['asdf'],
            ['stuff'],
        ];
    }

    /**
     * @dataProvider provideInvalidLower
     */
    public function testInvalidCtypeLower($text)
    {
        $this->assertFalse(ctype_lower($text));
    }

    public static function provideInvalidLower()
    {
        return [
            [[]],
            [true],
            [null],
            [new \stdClass()],
            [53.0],
            [25.4],
            [-129],
            [-386],
            ['asd df'],
            ['ADD'],
            ['123'],
            ['A1cbad'],
            ['!!'],
            [''],
            ['é'],
            ["\n"],
            ["\x00asdf"],
        ];
    }

    /**
     * @dataProvider provideValidPrints
     */
    public function testValidCtypePrint($text)
    {
        $this->assertTrue(ctype_print($text));
    }

    public static function provideValidPrints()
    {
        return [
            [-129],
            [-386],
            ['0'],
            [43],
            [53],
            [280],
            [65],
            [98],
            ['567'],
            ['!!'],
            ['@@!#^$'],
            ['asd df'],
        ];
    }

    /**
     * @dataProvider provideInvalidPrint
     */
    public function testInvalidCtypePrint($text)
    {
        $this->assertFalse(ctype_print($text));
    }

    public static function provideInvalidPrint()
    {
        return [
            [[]],
            [true],
            [null],
            [new \stdClass()],
            [53.0],
            [25.4],
            [8],
            [127],
            ['é'],
            ["\n"],
            ["\x00asdf"],
        ];
    }

    /**
     * @dataProvider provideValidPuncts
     */
    public function testValidCtypePunct($text)
    {
        $this->assertTrue(ctype_punct($text));
    }

    public static function provideValidPuncts()
    {
        return [
            [43],
            ['!!'],
            ['@@!#^$'],
        ];
    }

    /**
     * @dataProvider provideInvalidPunct
     */
    public function testInvalidCtypePunct($text)
    {
        $this->assertFalse(ctype_punct($text));
    }

    public static function provideInvalidPunct()
    {
        return [
            [[]],
            [true],
            [null],
            [new \stdClass()],
            [53.0],
            [25.4],
            [-129],
            [-386],
            [8],
            [53],
            [65],
            [98],
            [127],
            ['é'],
            ['asd df'],
            ['ADD'],
            ['123'],
            ['A1cbad'],
            [''],
            ["\n"],
            ["\x00asdf"],
        ];
    }

    /**
     * @dataProvider provideValidSpaces
     */
    public function testValidCtypeSpace($text)
    {
        $this->assertTrue(ctype_space($text));
    }

    public static function provideValidSpaces()
    {
        return [
            [32],
            ["\t"],
            ["\n"],
            ["\r\n"],
            ["\n\r"],
            ["\r"],
        ];
    }

    /**
     * @dataProvider provideInvalidSpace
     */
    public function testInvalidCtypeSpace($text)
    {
        $this->assertFalse(ctype_space($text));
    }

    public static function provideInvalidSpace()
    {
        return [
            [[]],
            [true],
            [null],
            [new \stdClass()],
            [53.0],
            [25.4],
            [-129],
            [-386],
            [8],
            [65],
            [98],
            [43],
            [127],
            [280],
            ['asdf'],
            ['123'],
            ["\x01"],
            [''],
            ['Ad12'],
            ['ADD'],
        ];
    }

    /**
     * @dataProvider provideValidUppers
     */
    public function testValidCtypeUpper($text)
    {
        $this->assertTrue(ctype_upper($text));
    }

    public static function provideValidUppers()
    {
        return [
            [65],
            ['ADD'],
            ['ASDF'],
            ['DDD'],
        ];
    }

    /**
     * @dataProvider provideInvalidUpper
     */
    public function testInvalidCtypeUpper($text)
    {
        $this->assertFalse(ctype_upper($text));
    }

    public static function provideInvalidUpper()
    {
        return [
            [[]],
            [true],
            [null],
            [new \stdClass()],
            [53.0],
            [25.4],
            [-129],
            [-386],
            [8],
            [43],
            [53],
            [98],
            [127],
            [280],
            [-129],
            [-128],
            ['asdf'],
            ['123'],
            ["\x01"],
            [''],
            ['Ad12'],
            ["\t"],
            ["\n"],
            ["\r\n"],
            ["\n\r"],
            ["\r"],
        ];
    }

    /**
     * @dataProvider provideValidXdigits
     */
    public function testValidCtypeXdigit($text)
    {
        $this->assertTrue(ctype_xdigit($text));
    }

    public static function provideValidXdigits()
    {
        return [
            ['0'],
            [53],
            [65],
            [98],
            [70],
            [102],
            [280],
            ['01234'],
            ['a0123'],
            ['A4fD'],
            ['DDD'],
            ['bbb'],
        ];
    }

    /**
     * @dataProvider provideInvalidXdigit
     */
    public function testInvalidCtypeXdigit($text)
    {
        $this->assertFalse(ctype_xdigit($text));
    }

    public static function provideInvalidXdigit()
    {
        return [
            [[]],
            [true],
            [null],
            [new \stdClass()],
            [53.0],
            [25.4],
            [-129],
            [-386],
            [43],
            [71],
            [103],
            [127],
            ['asdfk'],
            ['hhh'],
            ['0123kl'],
            ['zzz'],
            ["\x01"],
            [''],
            ["\t"],
            ["\n"],
            ["\r\n"],
            ["\n\r"],
            ["\r"],
        ];
    }
}
