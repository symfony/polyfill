<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Paragon Initiative Enterprises
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.This file is part of the Symfony package.
 *
 */

namespace Symfony\Polyfill\Tests\Php70;

/**
 * Test cases are borrowed from https://github.com/paragonie/random_compat
 */
class Php70RandomTest extends \PHPUnit_Framework_TestCase
{
    public function testRandomBytesOutput()
    {
        $bytes = array(
            random_bytes(12),
            random_bytes(64),
            random_bytes(64)
        );

        $this->assertTrue(strlen(bin2hex($bytes[0])) === 24);

        // This should never generate identical byte strings
        $this->assertFalse(
            $bytes[1] === $bytes[2]
        );
    }

    /**
     * @requires function gzcompress
     */
    public function testRandomBytesCompressionRatios()
    {
        $bytes = random_bytes(65536);

        $this->assertSame(65536, strlen($bytes));
        $compressed = gzcompress($bytes, 9);
        $length = strlen($compressed);

        $this->assertTrue(65000 <= $length && $length <= 67000);
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage random_bytes() expects parameter 1 to be integer
     */
    public function testRandomBytesTypeError()
    {
        random_bytes(PHP_INT_MAX + 1);
    }

    /**
     * @expectedException Error
     * @expectedExceptionMessage Length must be greater than 0
     */
    public function testRandomBytesLengthError()
    {
        random_bytes(0);
    }

    /**
     * All possible values should be > 30% but less than 170%
     *
     * This also catches 0 and 1000
     */
    public function testRandomIntDistribution()
    {
        $integers = array_fill(0, 100, 0);
        for ($i = 0; $i < 10000; ++$i) {
            ++$integers[random_int(0,99)];
        }
        for ($i = 0; $i < 100; ++$i) {
            $this->assertFalse($integers[$i] < 30);
            $this->assertFalse($integers[$i] > 170);
        }
    }

    /**
     * This should be between 55% and 75%, always
     */
    public function testRandomIntCoverage()
    {
        $integers = array_fill(0, 2000, 0);
        for ($i = 0; $i < 2000; ++$i) {
            ++$integers[random_int(0,1999)];
        }
        $coverage = 0;
        for ($i = 0; $i < 2000; ++$i) {
            if ($integers[$i] > 0) {
                ++$coverage;
            }
        }
        $this->assertTrue($coverage >= 1150);
        $this->assertTrue($coverage <= 1350);
    }

    public function testRandomInt()
    {
        $half_neg_max = (~PHP_INT_MAX / 2);
        $integers = array(
            random_int(0, 1000),
            random_int(1001,2000),
            random_int(-100, -10),
            random_int(-1000, 1000),
            random_int(~PHP_INT_MAX, PHP_INT_MAX),
            random_int("0", "1"),
            random_int(0.11111, 0.99999),
            random_int($half_neg_max, PHP_INT_MAX),
            random_int(0.0, 255.0),
            random_int(-4.5, -4.5),
            random_int("1337e3","1337e3")
        );

        $this->assertFalse($integers[0] === $integers[1]);
        $this->assertTrue($integers[0] >= 0 && $integers[0] <= 1000);
        $this->assertTrue($integers[1] >= 1001 && $integers[1] <= 2000);
        $this->assertTrue($integers[2] >= -100 && $integers[2] <= -10);
        $this->assertTrue($integers[3] >= -1000 && $integers[3] <= 1000);
        $this->assertTrue($integers[4] >= ~PHP_INT_MAX && $integers[4] <= PHP_INT_MAX);
        $this->assertTrue($integers[5] >= 0 && $integers[5] <= 1);
        $this->assertTrue($integers[6] === 0);
        $this->assertTrue($integers[7] >= $half_neg_max && $integers[7] <= PHP_INT_MAX);
        $this->assertTrue($integers[8] >= 0 && $integers[8] <= 255);
        $this->assertTrue($integers[9] === -4);
        $this->assertTrue($integers[10] === 1337000);
    }

    public function testRandomIntRange()
    {
        $try = 64;
        $maxLen = strlen(~PHP_INT_MAX);
        do {
            $rand = random_int(~PHP_INT_MAX, PHP_INT_MAX);
        } while (strlen($rand) !== $maxLen && $try--);

        $this->assertGreaterThan(0, $try);
    }

    /**
     * @expectedException Error
     * @expectedExceptionMessage Minimum value must be less than or equal to the maximum value
     */
    public function testRandomIntSwappedError()
    {
        random_int("0", "-1");
    }

    /**
     * @expectedException TypeError
     * @expectedExceptionMessage random_int() expects parameter 2 to be integer, float given
     */
    public function provideRandomIntError()
    {
        random_int(~PHP_INT_MAX, ~PHP_INT_MAX + 0.0);
    }
}
