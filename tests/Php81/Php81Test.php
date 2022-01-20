<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php81;

use PHPUnit\Framework\TestCase;

class Php81Test extends TestCase
{
    /**
     * @covers \Symfony\Polyfill\Php81\Php81::array_is_list
     */
    public function testArrayIsList()
    {
        $this->assertTrue(array_is_list([]));
        $this->assertTrue(array_is_list([\NAN, 'foo', 123]));
        $this->assertFalse(array_is_list([1 => 'a', 0 => 'b']));
        $this->assertFalse(array_is_list(['a' => 'b']));
        $this->assertFalse(array_is_list([0 => 'a', 2 => 'b']));
        $this->assertFalse(array_is_list([1 => 'a', 2 => 'b']));

        $x = ['key' => 2, NAN];
        unset($x['key']);
        $this->assertTrue(array_is_list($x));
    }

    /**
     * @requires extension mysqli
     */
    public function testMysqliRefreshReplicaDefined()
    {
        $this->assertTrue(defined('MYSQLI_REFRESH_REPLICA'));
        $this->assertSame(MYSQLI_REFRESH_SLAVE, MYSQLI_REFRESH_REPLICA);
    }
}
