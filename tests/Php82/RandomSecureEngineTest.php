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
        $this->assertSame(\PHP_INT_SIZE, strlen($v->generate()));
    }

    /**
     * @dataProvider secureEngineProvider
     */
    public function testSerializeIsNotAllowed($v)
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Serialization of 'Random\\Engine\\Secure' is not allowed");

        serialize($v);
    }
}
