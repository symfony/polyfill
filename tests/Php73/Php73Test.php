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
        require 'generator.php';

        $this->assertFalse(is_countable(genOneToTen()));
    }
}
