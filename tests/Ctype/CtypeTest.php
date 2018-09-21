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

class CtypeTest extends TestCase
{
    /**
     * @dataProvider provideValidAlnums
     */
    public function testValidCtypeAlnum($text)
    {
        $this->assertTrue(ctype_alnum($text));
    }

    public function provideValidAlnums()
    {
        return array(
            array('0'),
            array(53),
            array(65),
            array(98),
            array('asdf'),
            array('ADD'),
            array('123'),
            array('A1cbad'),
            array(280),
        );
    }

    /**
     * @dataProvider provideInvalidAlnum
     */
    public function testInvalidCtypeAlnum($text)
    {
        $this->assertFalse(ctype_alnum($text));
    }

    public function provideInvalidAlnum()
    {
        return array(
            array(array()),
            array(true),
            array(null),
            array(new \stdClass()),
            array(53.0),
            array(25.4),
            array(-129),
            array(-386),
            array(8),
            array(43),
            array(-127),
            array('asd df'),
            array(''),
            array('é'),
            array('!!'),
            array('!asdf'),
            array('as2!a'),
            array("\x00asdf"),
        );
    }

    /**
     * @dataProvider provideValidAlphas
     */
    public function testValidCtypeAlpha($text)
    {
        $this->assertTrue(ctype_alpha($text));
    }

    public function provideValidAlphas()
    {
        return array(
            array(65),
            array(98),
            array('asdf'),
            array('ADD'),
            array('bAcbad'),
        );
    }

    /**
     * @dataProvider provideInvalidAlpha
     */
    public function testInvalidCtypeAlpha($text)
    {
        $this->assertFalse(ctype_alpha($text));
    }

    public function provideInvalidAlpha()
    {
        return array(
            array(array()),
            array(true),
            array(null),
            array(new \stdClass()),
            array(53.0),
            array(25.4),
            array(-129),
            array(-386),
            array(8),
            array(43),
            array(53),
            array('asd df'),
            array(''),
            array('é'),
            array('1234'),
            array('13addfadsf2'),
            array("\x00asd"),
            array(280),
        );
    }

    /**
     * @dataProvider provideValidCntrls
     */
    public function testValidCtypeCntrl($text)
    {
        $this->assertTrue(ctype_cntrl($text));
    }

    public function provideValidCntrls()
    {
        return array(
            array(8),
            array(127),
            array("\x00"),
            array("\x02"),
            array(\chr(127)),
        );
    }

    /**
     * @dataProvider provideInvalidCntrl
     */
    public function testInvalidCtypeCntrl($text)
    {
        $this->assertFalse(ctype_cntrl($text));
    }

    public function provideInvalidCntrl()
    {
        return array(
            array(array()),
            array(true),
            array(null),
            array(new \stdClass()),
            array(53.0),
            array(25.4),
            array(-129),
            array(-386),
            array(53),
            array(65),
            array(98),
            array(43),
            array(280),
            array('asd df'),
            array(''),
            array('é'),
            array('1234'),
            array('13addfadsf2'),
            array("\x00adf"),
            array(\chr(127).'adf'),
        );
    }

    /**
     * @dataProvider provideValidDigits
     */
    public function testValidCtypeDigit($text)
    {
        $this->assertTrue(ctype_digit($text));
    }

    public function provideValidDigits()
    {
        return array(
            array('0'),
            array(53),
            array(280),
            array('123'),
            array('01234'),
            array('934'),
        );
    }

    /**
     * @dataProvider provideInvalidDigit
     */
    public function testInvalidCtypeDigit($text)
    {
        $this->assertFalse(ctype_digit($text));
    }

    public function provideInvalidDigit()
    {
        return array(
            array(array()),
            array(true),
            array(null),
            array(new \stdClass()),
            array(53.0),
            array(25.4),
            array(-129),
            array(-386),
            array(8),
            array(43),
            array(65),
            array(98),
            array(-129),
            array(-456),
            array('asd df'),
            array(''),
            array('é'),
            array('1234B'),
            array('13addfadsf2'),
            array("\x00a"),
            array(\chr(127), '-3', '3.5'),
        );
    }

    /**
     * @dataProvider provideValidGraphs
     */
    public function testValidCtypeGraph($text)
    {
        $this->assertTrue(ctype_graph($text));
    }

    public function provideValidGraphs()
    {
        return array(
            array(-129),
            array(-386),
            array('0'),
            array(43),
            array(53),
            array(65),
            array(98),
            array('asdf'),
            array('ADD'),
            array('123'),
            array('A1cbad'),
            array('!!'),
            array('!asdF'),
        );
    }

    /**
     * @dataProvider provideInvalidGraph
     */
    public function testInvalidCtypeGraph($text)
    {
        $this->assertFalse(ctype_graph($text));
    }

    public function provideInvalidGraph()
    {
        return array(
            array(array()),
            array(true),
            array(null),
            array(new \stdClass()),
            array(53.0),
            array(25.4),
            array(8),
            array(127),
            array('asd df'),
            array(''),
            array('é'),
            array("\n"),
            array("\x00asdf"),
        );
    }

    /**
     * @dataProvider provideValidLowers
     */
    public function testValidCtypeLower($text)
    {
        $this->assertTrue(ctype_lower($text));
    }

    public function provideValidLowers()
    {
        return array(
            array(98),
            array('asdf'),
            array('stuff'),
        );
    }

    /**
     * @dataProvider provideInvalidLower
     */
    public function testInvalidCtypeLower($text)
    {
        $this->assertFalse(ctype_lower($text));
    }

    public function provideInvalidLower()
    {
        return array(
            array(array()),
            array(true),
            array(null),
            array(new \stdClass()),
            array(53.0),
            array(25.4),
            array(-129),
            array(-386),
            array('asd df'),
            array('ADD'),
            array('123'),
            array('A1cbad'),
            array('!!'),
            array(''),
            array('é'),
            array("\n"),
            array("\x00asdf"),
        );
    }

    /**
     * @dataProvider provideValidPrints
     */
    public function testValidCtypePrint($text)
    {
        $this->assertTrue(ctype_print($text));
    }

    public function provideValidPrints()
    {
        return array(
            array(-129),
            array(-386),
            array('0'),
            array(43),
            array(53),
            array(280),
            array(65),
            array(98),
            array('567'),
            array('!!'),
            array('@@!#^$'),
            array('asd df'),
        );
    }

    /**
     * @dataProvider provideInvalidPrint
     */
    public function testInvalidCtypePrint($text)
    {
        $this->assertFalse(ctype_print($text));
    }

    public function provideInvalidPrint()
    {
        return array(
            array(array()),
            array(true),
            array(null),
            array(new \stdClass()),
            array(53.0),
            array(25.4),
            array(8),
            array(127),
            array('é'),
            array("\n"),
            array("\x00asdf"),
        );
    }

    /**
     * @dataProvider provideValidPuncts
     */
    public function testValidCtypePunct($text)
    {
        $this->assertTrue(ctype_punct($text));
    }

    public function provideValidPuncts()
    {
        return array(
            array(43),
            array('!!'),
            array('@@!#^$'),
        );
    }

    /**
     * @dataProvider provideInvalidPunct
     */
    public function testInvalidCtypePunct($text)
    {
        $this->assertFalse(ctype_punct($text));
    }

    public function provideInvalidPunct()
    {
        return array(
            array(array()),
            array(true),
            array(null),
            array(new \stdClass()),
            array(53.0),
            array(25.4),
            array(-129),
            array(-386),
            array(8),
            array(53),
            array(65),
            array(98),
            array(127),
            array('é'),
            array('asd df'),
            array('ADD'),
            array('123'),
            array('A1cbad'),
            array(''),
            array("\n"),
            array("\x00asdf"),
        );
    }

    /**
     * @dataProvider provideValidSpaces
     */
    public function testValidCtypeSpace($text)
    {
        $this->assertTrue(ctype_space($text));
    }

    public function provideValidSpaces()
    {
        return array(
            array(32),
            array("\t"),
            array("\n"),
            array("\r\n"),
            array("\n\r"),
            array("\r"),
        );
    }

    /**
     * @dataProvider provideInvalidSpace
     */
    public function testInvalidCtypeSpace($text)
    {
        $this->assertFalse(ctype_space($text));
    }

    public function provideInvalidSpace()
    {
        return array(
            array(array()),
            array(true),
            array(null),
            array(new \stdClass()),
            array(53.0),
            array(25.4),
            array(-129),
            array(-386),
            array(8),
            array(65),
            array(98),
            array(43),
            array(127),
            array(280),
            array('asdf'),
            array('123'),
            array("\x01"),
            array(''),
            array('Ad12'),
            array('ADD'),
        );
    }

    /**
     * @dataProvider provideValidUppers
     */
    public function testValidCtypeUpper($text)
    {
        $this->assertTrue(ctype_upper($text));
    }

    public function provideValidUppers()
    {
        return array(
            array(65),
            array('ADD'),
            array('ASDF'),
            array('DDD'),
        );
    }

    /**
     * @dataProvider provideInvalidUpper
     */
    public function testInvalidCtypeUpper($text)
    {
        $this->assertFalse(ctype_upper($text));
    }

    public function provideInvalidUpper()
    {
        return array(
            array(array()),
            array(true),
            array(null),
            array(new \stdClass()),
            array(53.0),
            array(25.4),
            array(-129),
            array(-386),
            array(8),
            array(43),
            array(53),
            array(98),
            array(127),
            array(280),
            array(-129),
            array(-128),
            array('asdf'),
            array('123'),
            array("\x01"),
            array(''),
            array('Ad12'),
            array("\t"),
            array("\n"),
            array("\r\n"),
            array("\n\r"),
            array("\r"),
        );
    }

    /**
     * @dataProvider provideValidXdigits
     */
    public function testValidCtypeXdigit($text)
    {
        $this->assertTrue(ctype_xdigit($text));
    }

    public function provideValidXdigits()
    {
        return array(
            array('0'),
            array(53),
            array(65),
            array(98),
            array(70),
            array(102),
            array(280),
            array('01234'),
            array('a0123'),
            array('A4fD'),
            array('DDD'),
            array('bbb'),
        );
    }

    /**
     * @dataProvider provideInvalidXdigit
     */
    public function testInvalidCtypeXdigit($text)
    {
        $this->assertFalse(ctype_xdigit($text));
    }

    public function provideInvalidXdigit()
    {
        return array(
            array(array()),
            array(true),
            array(null),
            array(new \stdClass()),
            array(53.0),
            array(25.4),
            array(-129),
            array(-386),
            array(43),
            array(71),
            array(103),
            array(127),
            array('asdfk'),
            array('hhh'),
            array('0123kl'),
            array('zzz'),
            array("\x01"),
            array(''),
            array("\t"),
            array("\n"),
            array("\r\n"),
            array("\n\r"),
            array("\r"),
        );
    }
}
