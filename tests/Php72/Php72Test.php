<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php72;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @covers Symfony\Polyfill\Php72\Php72::<!public>
 */
class Php72Test extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Symfony\Polyfill\Php72\Php72::utf8_encode
     * @covers Symfony\Polyfill\Php72\Php72::utf8_decode
     */
    public function testUtf8Encode()
    {
        $s = array_map('chr', range(0, 255));
        $s = implode('', $s);
        $e = utf8_encode($s);

        $this->assertSame(\utf8_encode($s), utf8_encode($s));
        $this->assertSame(\utf8_decode($e), utf8_decode($e));
        $this->assertSame('??', utf8_decode('Σ어'));

        $s = 444;

        $this->assertSame(\utf8_encode($s), utf8_encode($s));
        $this->assertSame(\utf8_decode($s), utf8_decode($s));
    }
}
