<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php54;

class Php54Test extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideClassUsesValid
     */
    public function testClassUsesValid($classOrObject)
    {
        $this->assertSame(array(), class_uses($classOrObject));
    }

    public function provideClassUsesValid()
    {
        return array(
            array('stdClass'),
            array(new \stdClass()),
            array('Iterator'),
        );
    }

    public function testClassUsesInvalid()
    {
        $this->assertFalse(@class_uses('NotDefined'));
    }
}
