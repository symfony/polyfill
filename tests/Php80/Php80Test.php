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
 * @author Ion Bazan <ion.bazan@gmail.com>
 */
class Php80Test extends TestCase
{
    /**
     * @requires PHP 7.0
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
     * @requires PHP 7.0
     * @covers \Symfony\Polyfill\Php80\Php80::fdiv
     * @dataProvider nanFdivProvider
     */
    public function testFdivNan($divident, $divisor)
    {
        $this->assertTrue(is_nan(fdiv($divident, $divisor)));
    }

    /**
     * @requires PHP 7.0
     * @covers \Symfony\Polyfill\Php80\Php80::fdiv
     * @dataProvider invalidFloatProvider
     */
    public function testFdivTypeError($divident, $divisor)
    {
        $this->setExpectedException('\TypeError');
        fdiv($divident, $divisor);
    }

    /**
     * @requires PHP 7.0
     */
    public function testFilterValidateBool()
    {
        $this->assertTrue(\defined('FILTER_VALIDATE_BOOL'));
        $this->assertSame(FILTER_VALIDATE_BOOLEAN, FILTER_VALIDATE_BOOL);
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
            array(1.0, false),
            array(1.0, true),
        );
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
