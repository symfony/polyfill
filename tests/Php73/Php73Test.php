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
 */
class Php73Test extends TestCase
{
    public function testIsCountable()
    {
        $this->assertTrue(is_countable(array(1, 2, '3')));
        $this->assertTrue(is_countable(new \ArrayIterator(array('foo', 'bar', 'baz'))));
        $this->assertTrue(is_countable(new \ArrayIterator()));
        $this->assertTrue(is_countable(new \SimpleXMLElement('<foo><bar/><bar/><bar/></foo>')));
        $this->assertTrue(is_countable(\ResourceBundle::create('en', __DIR__.'/fixtures')));
        $this->assertFalse(is_countable(new \stdClass()));
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

        usleep(1000000);
        $hrtime2 = hrtime(true);

        $this->assertGreaterThanOrEqual(1000000000, $hrtime2 - $hrtime);
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
}
