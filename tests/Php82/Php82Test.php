<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php82;

use PHPUnit\Framework\TestCase;

class Php82Test extends TestCase
{
    /**
     * @covers \Symfony\Polyfill\Php82\Php82::ini_parse_quantity
     */
    public function testIniParseQuantity()
    {
        $this->assertSame(1024, ini_parse_quantity('1K'));
        $this->assertSame(1024, ini_parse_quantity('1k'));
        $this->assertSame(1024 * 1024 * 5, ini_parse_quantity('5M'));
        $this->assertSame(1024 * 1024 * 5, ini_parse_quantity('5 M'));
        $this->assertSame(1024 * 1024 * -5, ini_parse_quantity('-5M'));
        $this->assertSame(-696969, ini_parse_quantity('-696969'));
        $this->assertSame(696969, ini_parse_quantity('696969'));
    }
}
