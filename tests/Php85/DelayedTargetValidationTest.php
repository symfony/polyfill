<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php85;

use PHPUnit\Framework\TestCase;

#[\DelayedTargetValidation]
class HasAttribute
{
    #[\DelayedTargetValidation]
    private $prop;

    #[\DelayedTargetValidation]
    public const FOO = 'BAR';

    #[\DelayedTargetValidation]
    public function __construct(
        #[\DelayedTargetValidation] $param,
    ) {
    }
}

#[\DelayedTargetValidation]
function globalFunc()
{
}

/**
 * @author Daniel Scherzer <daniel.e.scherzer@gmail.com>
 *
 * @requires PHP >= 8.0
 */
class DelayedTargetValidationTest extends TestCase
{
    public function testAttributeAttribute()
    {
        $ref = new \ReflectionClass(\DelayedTargetValidation::class);
        $attributes = $ref->getAttributes();
        $this->assertCount(1, $attributes);
        $attribute = $attributes[0];
        $this->assertSame('Attribute', $attribute->getName());
        $args = $attribute->getArguments();
        $this->assertCount(1, $args);
        $this->assertSame(\Attribute::TARGET_ALL, $args[0]);
        $this->expectException(\ReflectionException::class);
        $this->expectExceptionMessage('Constant "missing" does not exist');
        new \ReflectionConstant('missing');
    }

    /**
     * @dataProvider provideReflectionInstances
     */
    public function testTargetValidation($reflectionSource)
    {
        $attributes = $reflectionSource->getAttributes();
        $this->assertCount(1, $attributes);
        $attrib = $attributes[0];
        $this->assertSame('DelayedTargetValidation', $attrib->getName());
        $this->assertSame([], $attrib->getArguments());
        $this->assertInstanceOf(
            \DelayedTargetValidation::class,
            $attrib->newInstance()
        );
    }

    public static function provideReflectionInstances()
    {
        yield 'Class' => [new \ReflectionClass(HasAttribute::class)];
        yield 'Property' => [new \ReflectionProperty(HasAttribute::class, 'prop')];
        yield 'Class constant' => [new \ReflectionClassConstant(HasAttribute::class, 'FOO')];
        yield 'Method' => [new \ReflectionMethod(HasAttribute::class, '__construct')];
        yield 'Parameter' => [
            new \ReflectionParameter([HasAttribute::class, '__construct'], 'param'),
        ];
        yield 'Function' => [new \ReflectionFunction(__NAMESPACE__.'\\globalFunc')];
    }
}
