<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php80;

use PHPUnit\Framework\TestCase;

/**
 * @requires PHP 7.0
 *
 * @author Ion Bazan <ion.bazan@gmail.com>
 * @author Nico Oelgart <nicoswd@gmail.com>
 */
class Php80Test extends TestCase
{
    /**
     * @covers \Symfony\Polyfill\Php80\Php80::fdiv
     * @dataProvider fdivProvider
     */
    public function testFdiv($expected, $divident, $divisor)
    {
        $result = fdiv($divident, $divisor);
        $this->assertSame($expected, $result);
        // Cast to string to detect negative zero "-0"
        $this->assertSame((string) $expected, (string) $result);
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::fdiv
     * @dataProvider nanFdivProvider
     */
    public function testFdivNan($divident, $divisor)
    {
        $this->assertTrue(is_nan(fdiv($divident, $divisor)));
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::fdiv
     * @dataProvider invalidFloatProvider
     */
    public function testFdivTypeError($divident, $divisor)
    {
        $this->setExpectedException('TypeError');
        fdiv($divident, $divisor);
    }

    public function testFilterValidateBool()
    {
        $this->assertTrue(\defined('FILTER_VALIDATE_BOOL'));
        $this->assertSame(FILTER_VALIDATE_BOOLEAN, FILTER_VALIDATE_BOOL);
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::preg_last_error_msg
     */
    public function testPregNoError()
    {
        $this->assertSame('No error', preg_last_error_msg());
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::preg_last_error_msg
     */
    public function testPregMalformedUtfError()
    {
        @preg_split('/a/u', "a\xff");
        $this->assertSame('Malformed UTF-8 characters, possibly incorrectly encoded', preg_last_error_msg());
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::preg_last_error_msg
     */
    public function testPregMalformedUtf8Offset()
    {
        @preg_match('/a/u', "\xE3\x82\xA2", $m, 0, 1);
        $this->assertSame(
            'The offset did not correspond to the beginning of a valid UTF-8 code point',
            preg_last_error_msg()
        );
    }

    public function fdivProvider()
    {
        return array(
            array(3.3333333333333, '10', '3'),
            array(3.3333333333333, 10.0, 3.0),
            array(-4.0, -10.0, 2.5),
            array(-4.0, 10.0, -2.5),
            array(INF, 10.0, 0.0),
            array(-INF, 10.0, -0.0),
            array(-INF, -10.0, 0.0),
            array(INF, -10.0, -0.0),
            array(INF, INF, 0.0),
            array(-INF, INF, -0.0),
            array(-INF, -INF, 0.0),
            array(INF, -INF, -0.0),
            array(0.0, 0.0, INF),
            array(-0.0, 0.0, -INF),
            array(-0.0, -0.0, INF),
            array(0.0, -0.0, -INF),
        );
    }

    public function nanFdivProvider()
    {
        return array(
            array(0.0, 0.0),
            array(0.0, -0.0),
            array(-0.0, 0.0),
            array(-0.0, -0.0),
            array(INF, INF),
            array(INF, -INF),
            array(-INF, INF),
            array(-INF, -INF),
            array(NAN, NAN),
            array(INF, NAN),
            array(-0.0, NAN),
            array(NAN, INF),
            array(NAN, 0.0),
        );
    }

    public function invalidFloatProvider()
    {
        return array(
            array('invalid', 1.0),
            array('invalid', 'invalid'),
            array(1.0, 'invalid'),
        );
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::get_debug_type
     */
    public function testGetDebugType()
    {
        $this->assertSame(__CLASS__, get_debug_type($this));
        $this->assertSame('stdClass', get_debug_type(new \stdClass()));
        $this->assertSame('class@anonymous', get_debug_type(eval('return new class() {};')));
        $this->assertSame('stdClass@anonymous', get_debug_type(eval('return new class() extends stdClass {};')));
        $this->assertSame('Reflector@anonymous', get_debug_type(eval('return new class() implements Reflector { function __toString() {} public static function export() {} };')));

        $this->assertSame('string', get_debug_type('foo'));
        $this->assertSame('bool', get_debug_type(false));
        $this->assertSame('bool', get_debug_type(true));
        $this->assertSame('null', get_debug_type(null));
        $this->assertSame('array', get_debug_type(array()));
        $this->assertSame('int', get_debug_type(1));
        $this->assertSame('float', get_debug_type(1.2));
        $this->assertSame('resource (stream)', get_debug_type($h = fopen(__FILE__, 'r')));
        $this->assertSame('resource (closed)', get_debug_type(fclose($h) ? $h : $h));

        $unserializeCallbackHandler = ini_set('unserialize_callback_func', null);
        $var = unserialize('O:8:"Foo\Buzz":0:{}');
        ini_set('unserialize_callback_func', $unserializeCallbackHandler);

        $this->assertSame('__PHP_Incomplete_Class', get_debug_type($var));
    }

    public function setExpectedException($exception, $message = '', $code = null)
    {
        if (!class_exists('PHPUnit\Framework\Error\Notice')) {
            $exception = str_replace('PHPUnit\\Framework\\Error\\', 'PHPUnit_Framework_Error_', $exception);
        }
        if (method_exists($this, 'expectException')) {
            $this->expectException($exception);
            if (!empty($message)) {
                $this->expectExceptionMessage($message);
            }
        } else {
            parent::setExpectedException($exception, $message, $code);
        }
    }
}
