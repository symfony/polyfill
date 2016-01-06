<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php70;

class Php70Test extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideIntdiv
     */
    public function testIntdiv($expected, $dividend, $divisor)
    {
        $this->assertSame($expected, intdiv($dividend, $divisor));
    }

    public function provideIntdiv()
    {
        return array(
            array(1, 3, 2),
            array(-1, -3, 2),
            array(-1, 3, -2),
            array(1, -3, -2),
            array(1, PHP_INT_MAX, PHP_INT_MAX),
            array(1, ~PHP_INT_MAX, ~PHP_INT_MAX),
        );
    }

    /**
     * @expectedException ArithmeticError
     */
    public function testIntdivArithmetic()
    {
        intdiv(~PHP_INT_MAX, -1);
    }

    /**
     * @expectedException DivisionByZeroError
     */
    public function testIntdivByZero()
    {
        intdiv(1, 0);
    }

    public function testPregReplaceCallbackArray()
    {
        $this->assertSame('ddda', preg_replace_callback_array(
            array(
                '/[^a]/' => function () {return 'a';},
                '/a/' => function () {return 'd';},
            ),
            'abca',
            3,
            $count
        ));

        $this->assertSame(5, $count);
    }

    public function testCatchExceptionBeforeThrowable()
    {
        try {
            throw new \Error('Catch me, if you can');
        } catch (\Exception $e) {
            $this->assertTrue(PHP_VERSION_ID < 70000, 'This will only work in PHP5');
        } catch (\Throwable $t) {
            $this->assertTrue(PHP_VERSION_ID >= 70000, 'This will only work in PHP7');
        }
    }

    public function testCatchThrowableBeforeException()
    {
        try {
            throw new \Error('Catch me, if you can');
        } catch (\Throwable $t) {
            $this->assertTrue(PHP_VERSION_ID >= 70000, 'This will only work in PHP7');
        } catch (\Exception $e) {
            $this->assertTrue(PHP_VERSION_ID < 70000, 'This will only work in PHP5');
        }
    }
}
