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
use Symfony\Polyfill\Php82\SensitiveParameterValue as SensitiveParameterValuePolyfill;

/**
 * @author Tim Düsterhus <duesterhus@woltlab.com>
 */
class SensitiveParameterValueTest extends TestCase
{
    public function sensitiveParameterValueProvider()
    {
        yield [new SensitiveParameterValuePolyfill('secret')];
        yield [new \SensitiveParameterValue('secret')];
    }

    /**
     * @dataProvider sensitiveParameterValueProvider
     */
    public function testGetValue($v)
    {
        $this->assertSame('secret', $v->getValue());
    }

    /**
     * @dataProvider sensitiveParameterValueProvider
     */
    public function testSerializeIsNotAllowed($v)
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Serialization of 'SensitiveParameterValue' is not allowed");

        serialize($v);
    }

    /**
     * @dataProvider sensitiveParameterValueProvider
     */
    public function testVarDumpDoesNotLeak($v)
    {
        ob_start();
        var_dump($v);
        $contents = ob_get_clean();

        $this->assertStringNotContainsString('secret', $contents);
    }

    /**
     * @dataProvider sensitiveParameterValueProvider
     */
    public function testDebugZvalDumpDoesNotLeak($v)
    {
        ob_start();
        debug_zval_dump($v);
        $contents = ob_get_clean();

        $this->assertStringNotContainsString('secret', $contents);
    }

    /**
     * @dataProvider sensitiveParameterValueProvider
     */
    public function testClone($v)
    {
        $clone = clone $v;

        $this->assertSame('secret', $clone->getValue());
    }
}
