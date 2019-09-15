<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php73;

use PHPUnit\Framework\TestCase;

/**
 * @author Gabriel Caruso <carusogabriel34@gmail.com>
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class Php73Test extends TestCase
{
    public function testIsCountable()
    {
        $this->assertTrue(is_countable(array(1, 2, '3')));
        $this->assertTrue(is_countable(new \ArrayIterator(array('foo', 'bar', 'baz'))));
        $this->assertTrue(is_countable(new \ArrayIterator()));
        $this->assertTrue(is_countable(new \SimpleXMLElement('<foo><bar/><bar/><bar/></foo>')));
        $this->assertFalse(is_countable(new \stdClass()));

        $endianBytes = unpack('S', "\x01\x00");
        if (1 === $endianBytes[1] && class_exists('ResourceBundle')) { // skip on big endian systems: the fixture is only for little endian ones
            $this->assertTrue(is_countable(\ResourceBundle::create('en', __DIR__.'/fixtures')));
        }
    }

    /**
     * @requires PHP 5.5
     */
    public function testIsCountableForGenerator()
    {
        require_once 'generator.php';

        $this->assertFalse(is_countable(genOneToTen()));
    }

    public function testHardwareTimeAsNumType()
    {
        $hrtime = hrtime(true);
        if (PHP_INT_SIZE === 4) {
            $this->assertInternalType('float', $hrtime);
            $this->assertEquals(floor($hrtime), $hrtime);
        } else {
            $this->assertInternalType('int', $hrtime);
        }
    }

    public function testHardwareTimeAsNum()
    {
        $hrtime = hrtime(true);
        usleep(100000);
        $hrtime2 = hrtime(true);

        if (PHP_INT_SIZE === 4) {
            $this->assertGreaterThanOrEqual(90000000.0, $hrtime2 - $hrtime);
        } else {
            $this->assertGreaterThanOrEqual(100000000.0, $hrtime2 - $hrtime);
        }
    }

    public function testHardwareTimeAsArrayType()
    {
        $hrtime = hrtime();
        $this->assertInternalType('array', $hrtime);
        $this->assertCount(2, $hrtime);
        $this->assertInternalType('int', $hrtime[0]);
        $this->assertInternalType('int', $hrtime[1]);
    }

    public function testHardwareTimeAsArrayNanos()
    {
        $hrtime = hrtime();
        usleep(1000);
        $hrtime2 = hrtime();

        $this->assertSame(0, $hrtime2[0] - $hrtime[0]);
        $this->assertGreaterThanOrEqual(1000000, $hrtime2[1] - $hrtime[1]);
    }

    public function testHardwareTimeAsArraySeconds()
    {
        $hrtime = hrtime();
        usleep(1000000);
        $hrtime2 = hrtime();

        $this->assertGreaterThanOrEqual(1, $hrtime2[0] - $hrtime[0]);
        $this->assertGreaterThanOrEqual(0, $hrtime2[1] - $hrtime[1]);
    }

    /**
     * @dataProvider arrayKeyFirstDataProvider
     */
    public function testArrayKeyFirst($expected, array $array)
    {
        $this->assertSame($expected, array_key_first($array));
    }

    public function testArrayKeyFirstVariation()
    {
        $array = array(1, 2, 3, 4, 5, 6, 7, 8, 9);

        $this->assertSame(1, current($array));
        $this->assertSame(2, next($array));
        $this->assertSame(0, array_key_first($array));
        $this->assertSame(2, current($array));
    }

    /**
     * @dataProvider arrayKeyLastDataProvider
     */
    public function testArrayLastFirst($expected, array $array)
    {
        $this->assertSame($expected, array_key_last($array));
    }

    public function testArrayKeyLastVariation()
    {
        $array = array(1, 2, 3, 4, 5, 6, 7, 8, 9);

        $this->assertSame(1, current($array));
        $this->assertSame(2, next($array));
        $this->assertSame(8, array_key_last($array));
        $this->assertSame(2, current($array));
    }

    public function arrayKeyFirstDataProvider()
    {
        return array(
            array(null,  array()),
            array(0,     array(1, 2, 3, 4, 5, 6, 7, 8, 9)),
            array(0,     array('One', '_Two', 'Three', 'Four', 'Five')),
            array(0,     array(6, 'six', 7, 'seven', 8, 'eight', 9, 'nine')),
            array('a',   array('a' => 'aaa', 'A' => 'AAA', 'c' => 'ccc', 'd' => 'ddd', 'e' => 'eee')),
            array(1,     array('1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four', '5' => 'five')),
            array(1,     array(1 => 'one', 2 => 'two', 3 => 7, 4 => 'four', 5 => 'five')),
            array('f',   array('f' => 'fff', '1' => 'one', 4 => 6, '' => 'blank', 2.4 => 'float', 'F' => 'FFF', 'blank' => '', 3.7 => 3.7, 5.4 => 7, 6 => 8.6, '5' => 'Five', '4name' => 'jonny', 'a' => null, null => 3)),
            array(0,     array(12, 'name', 'age', '45')),
            array(0,     array(array('oNe', 'tWo', 4), array(10, 20, 30, 40, 50), array())),
            array('one', array('one' => 1, 'one' => 2, 'three' => 3, 3, 4, 3 => 33, 4 => 44, 5, 6, 5.4 => 54, 5.7 => 57, '5.4' => 554, '5.7' => 557)),
            array(0,     array('foo')),
            array(1,     array(1 => '42')),
        );
    }

    public function arrayKeyLastDataProvider()
    {
        return array(
            array(null,  array()),
            array(8,     array(1, 2, 3, 4, 5, 6, 7, 8, 9)),
            array(4,     array('One', '_Two', 'Three', 'Four', 'Five')),
            array(7,     array(6, 'six', 7, 'seven', 8, 'eight', 9, 'nine')),
            array('e',   array('a' => 'aaa', 'A' => 'AAA', 'c' => 'ccc', 'd' => 'ddd', 'e' => 'eee')),
            array(5,     array('1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four', '5' => 'five')),
            array(5,     array(1 => 'one', 2 => 'two', 3 => 7, 4 => 'four', 5 => 'five')),
            array('a',   array('f' => 'fff', '1' => 'one', 4 => 6, '' => 'blank', 2.4 => 'float', 'F' => 'FFF', 'blank' => '', 3.7 => 3.7, 5.4 => 7, 6 => 8.6, '5' => 'Five', '4name' => 'jonny', 'a' => null, null => 3)),
            array(3,     array(12, 'name', 'age', '45')),
            array(2,     array(array('oNe', 'tWo', 4), array(10, 20, 30, 40, 50), array())),
            array('5.7', array('one' => 1, 'one' => 2, 'three' => 3, 3, 4, 3 => 33, 4 => 44, 5, 6, 5.4 => 54, 5.7 => 57, '5.4' => 554, '5.7' => 557)),
            array(0,     array('foo')),
            array(1,     array(1 => '42')),
        );
    }
}
