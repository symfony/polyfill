<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php84;

use PHPUnit\Framework\TestCase;

const EXAMPLE = 'Foo';

class ExampleNonStringable
{
    protected $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}

class ExampleStringable extends ExampleNonStringable
{
    public function __toString(): string
    {
        return 'ExampleStringable (value='.$this->value.')';
    }
}

/**
 * @author Daniel Scherzer <daniel.e.scherzer@gmail.com>
 */
class ReflectionConstantTest extends TestCase
{
    public function testMissingConstant()
    {
        $this->expectException(\ReflectionException::class);
        $this->expectExceptionMessage('Constant "missing" does not exist');
        new \ReflectionConstant('missing');
    }

    public function testClassConstant()
    {
        $this->assertTrue(\defined('ReflectionFunction::IS_DEPRECATED'));

        $this->expectException(\ReflectionException::class);
        $this->expectExceptionMessage('Constant "ReflectionClass::IS_DEPRECATED" does not exist');
        new \ReflectionConstant('ReflectionClass::IS_DEPRECATED');
    }

    public function testBuiltInConstant()
    {
        $constant = new \ReflectionConstant('E_ERROR');
        $this->assertSame('E_ERROR', $constant->name);
        $this->assertSame('E_ERROR', $constant->getName());
        $this->assertSame('', $constant->getNamespaceName());
        $this->assertSame('E_ERROR', $constant->getShortName());
        $this->assertSame(\E_ERROR, $constant->getValue());
        $this->assertFalse($constant->isDeprecated());
        $this->assertSame("Constant [ <persistent> int E_ERROR ] { 1 }\n", (string) $constant);
    }

    public function testUserConstant()
    {
        \define('TESTING', 123);

        $constant = new \ReflectionConstant('TESTING');
        $this->assertSame('TESTING', $constant->name);
        $this->assertSame('TESTING', $constant->getName());
        $this->assertSame('', $constant->getNamespaceName());
        $this->assertSame('TESTING', $constant->getShortName());
        $this->assertSame(TESTING, $constant->getValue());
        $this->assertFalse($constant->isDeprecated());
        $this->assertSame("Constant [ int TESTING ] { 123 }\n", (string) $constant);
    }

    public function testNamespacedConstant()
    {
        $constant = new \ReflectionConstant(EXAMPLE::class);
        $this->assertSame(EXAMPLE::class, $constant->name);
        $this->assertSame(EXAMPLE::class, $constant->getName());
        $this->assertSame(__NAMESPACE__, $constant->getNamespaceName());
        $this->assertSame('EXAMPLE', $constant->getShortName());
        $this->assertSame(EXAMPLE, $constant->getValue());
        $this->assertFalse($constant->isDeprecated());
        $this->assertSame("Constant [ string Symfony\\Polyfill\\Tests\\Php84\\EXAMPLE ] { Foo }\n", (string) $constant);
    }

    public function testDeprecated()
    {
        $constant = new \ReflectionConstant('MT_RAND_PHP');
        $this->assertSame('MT_RAND_PHP', $constant->name);
        $this->assertSame('MT_RAND_PHP', $constant->getName());
        $this->assertSame('', $constant->getNamespaceName());
        $this->assertSame('MT_RAND_PHP', $constant->getShortName());
        $this->assertSame(1, $constant->getValue());
        if (\PHP_VERSION_ID >= 80300) {
            $this->assertTrue($constant->isDeprecated());
            $this->assertSame("Constant [ <persistent, deprecated> int MT_RAND_PHP ] { 1 }\n", (string) $constant);
        } else {
            $this->assertFalse($constant->isDeprecated());
            $this->assertSame("Constant [ <persistent> int MT_RAND_PHP ] { 1 }\n", (string) $constant);
        }
    }

    public function testNonStringable()
    {
        if (\PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Constants can only be objects since PHP 8.1');
        }
        $value = new ExampleNonStringable('Testing');
        \define('NonStringable', $value);

        $constant = new \ReflectionConstant('NonStringable');

        // No error version of expectException()
        try {
            (string) $constant;
            $this->fail('Error should be thrown');
        } catch (\Error $e) {
            $this->assertSame("Object of class Symfony\Polyfill\Tests\Php84\ExampleNonStringable could not be converted to string", $e->getMessage());
        }
    }

    public function testStringable()
    {
        if (\PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Constants can only be objects since PHP 8.1');
        }
        $value = new ExampleStringable('Testing');
        \define('IsStringable', $value);

        $constant = new \ReflectionConstant('IsStringable');

        $this->assertSame("Constant [ Symfony\Polyfill\Tests\Php84\ExampleStringable IsStringable ] { ExampleStringable (value=Testing) }\n", (string) $constant);
    }

    public function testSerialization()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Serialization of 'ReflectionConstant' is not allowed");

        $r = new \ReflectionConstant('PHP_VERSION');
        serialize($r);
    }

    public function testUnserialization()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Unserialization of 'ReflectionConstant' is not allowed");
        unserialize(
            'O:18:"ReflectionConstant":4:{s:4:"name";'.
            "s:11:\"PHP_VERSION\";s:25:\"\0ReflectionConstant\0value\";s:6:\"8.3.19\";".
            "s:30:\"\0ReflectionConstant\0deprecated\";b:0;".
            "s:30:\"\0ReflectionConstant\0persistent\";b:1;}"
        );
    }
}
