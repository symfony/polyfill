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
use Symfony\Polyfill\Php82\Random\Engine\Secure as SecureEnginePolyfill;

/**
 * @author Tim Düsterhus <tim@bastelstu.be>
 */
class RandomSecureEngineTest extends TestCase
{
    public function secureEngineProvider()
    {
        yield [new SecureEnginePolyfill()];
        yield [new \Random\Engine\Secure()];
    }

    /**
     * @dataProvider secureEngineProvider
     */
    public function testGenerateLength($v)
    {
        $this->assertSame(\PHP_INT_SIZE, \strlen($v->generate()));
    }

    /**
     * @dataProvider secureEngineProvider
     */
    public function testCloneIsNotAllowed($v)
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage("Trying to clone an uncloneable object of class Random\Engine\Secure");

        clone $v;
    }

    /**
     * @dataProvider secureEngineProvider
     */
    public function testSerializeIsNotAllowed($v)
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Serialization of 'Random\Engine\Secure' is not allowed");

        serialize($v);
    }

    /**
     * @dataProvider secureEngineProvider
     */
    public function testUnserializeIsNotAllowed($v)
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches("{Unserialization of '.*Random\\\\Engine\\\\Secure' is not allowed}");

        unserialize(sprintf('O:%d:"%s":0:{}', \strlen(\get_class($v)), \get_class($v)));
    }
}
