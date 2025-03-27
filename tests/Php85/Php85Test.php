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

class Php85Test extends TestCase
{
    /**
    * @dataProvider provideHandler
    */
    public function testGetErrorHandler($expected, $handler): void
    {
        set_error_handler($handler);
        try {
            $result = get_error_handler();
        } finally {
            restore_error_handler();
        }

        $this->assertSame($expected, $result);
    }

    public function testErrorStableReturnValue(): void
    {
        $this->assertSame(get_error_handler(), get_error_handler());
    }

    /**
    * @dataProvider provideHandler
    */
    public function testGetExceptionHandler($expected, $handler): void
    {
        set_exception_handler($handler);
        try {
            $result = get_exception_handler();
        } finally {
            restore_exception_handler();
        }

        $this->assertSame($expected, $result);
    }

    public function testExceptionStableReturnValue(): void
    {
        $this->assertSame(get_exception_handler(), get_exception_handler());

    }

    public static function provideHandler()
    {
        // String handler
        yield ['var_dump', 'var_dump'];

        // Null handler
        yield [null, null];

        // Static method array handler
        yield [[TestHandler::class, 'handleStatic'], [TestHandler::class, 'handleStatic']];

        // Static method string handler
        yield ['Symfony\Polyfill\Tests\Php85\TestHandler::handleStatic', 'Symfony\Polyfill\Tests\Php85\TestHandler::handleStatic'];

        // Instance method array
        $handler = new TestHandler();
        yield [[$handler, 'handle'], [$handler, 'handle']];

        // Invokable object
        $handler = new TestHandlerInvokable();
        yield [$handler, $handler];
    }
}

class TestHandler
{
    public static function handleStatic() {}
    public function handle() {}
}

class TestHandlerInvokable
{
    public function __invoke() {}
}
