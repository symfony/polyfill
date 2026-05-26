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
    public function testGetErrorHandler($expected, $handler)
    {
        set_error_handler($handler);
        try {
            $result = get_error_handler();
        } finally {
            restore_error_handler();
        }

        $this->assertSame($expected, $result);
    }

    public function testErrorStableReturnValue()
    {
        $this->assertSame(get_error_handler(), get_error_handler());
    }

    /**
     * @dataProvider provideHandler
     */
    public function testGetExceptionHandler($expected, $handler)
    {
        set_exception_handler($handler);
        try {
            $result = get_exception_handler();
        } finally {
            restore_exception_handler();
        }

        $this->assertSame($expected, $result);
    }

    public function testExceptionStableReturnValue()
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

    public function testArrayFirstArrayLast()
    {
        $this->assertNull(array_first([]));
        $this->assertNull(array_last([]));

        $array = [1, 2, 3];
        unset($array[0], $array[1], $array[2]);

        $this->assertNull(array_first([]));
        $this->assertNull(array_last([]));

        $this->assertSame('single element', array_first(['single element']));
        $this->assertSame('single element', array_last(['single element']));

        $str = 'hello world';
        $this->assertSame($str, array_first([&$str, 1]));
        $this->assertSame(1, array_last([&$str, 1]));

        $this->assertSame(1, array_first([1, &$str]));
        $this->assertSame($str, array_last([1, &$str]));

        $this->assertSame(1, array_first([1 => 1, 0 => 0, 3 => 3, 2 => 2]));
        $this->assertSame(2, array_last([1 => 1, 0 => 0, 3 => 3, 2 => 2]));

        $this->assertSame('a1', array_first(['a' => 'a1', 'b' => 'b1', 'c' => 'c1']));
        $this->assertSame('c1', array_last(['a' => 'a1', 'b' => 'b1', 'c' => 'c1']));

        $this->assertSame([], array_first([100 => []]));
        $this->assertSame([], array_last([100 => []]));

        $this->assertEquals(new \stdClass(), array_first([new \stdClass(), false]));
        $this->assertFalse(array_last([new \stdClass(), false]));

        $this->assertTrue(array_first([true, new \stdClass()]));
        $this->assertEquals(new \stdClass(), array_last([true, new \stdClass()]));
    }

    public function testFilterExceptionClassesExist()
    {
        $this->assertTrue(class_exists(\Filter\FilterException::class));
        $this->assertTrue(class_exists(\Filter\FilterFailedException::class));
    }

    /**
     * @requires extension intl
     */
    public function testLocaleIsRightToLeft()
    {
        $this->assertTrue(locale_is_right_to_left('ar'));
        $this->assertTrue(locale_is_right_to_left('he'));
        $this->assertTrue(locale_is_right_to_left('fa'));
        $this->assertTrue(locale_is_right_to_left('ur'));
        $this->assertTrue(locale_is_right_to_left('ps'));
        $this->assertTrue(locale_is_right_to_left('sd'));
        $this->assertTrue(locale_is_right_to_left('ug'));
        $this->assertTrue(locale_is_right_to_left('ckb'));
        $this->assertTrue(locale_is_right_to_left('yi'));
        $this->assertTrue(locale_is_right_to_left('dv'));
        $this->assertTrue(locale_is_right_to_left('nqo'));

        // Explicit RTL script subtag
        $this->assertTrue(locale_is_right_to_left('ku_Arab'));
        $this->assertTrue(locale_is_right_to_left('ku-arab'));
        $this->assertTrue(locale_is_right_to_left('ar-EG'));

        // Explicit LTR script subtag overrides language default
        $this->assertFalse(locale_is_right_to_left('ar-Latn'));
        $this->assertFalse(locale_is_right_to_left('he-Latn'));

        // LTR locales
        $this->assertFalse(locale_is_right_to_left('en'));
        $this->assertFalse(locale_is_right_to_left('fr'));
        $this->assertFalse(locale_is_right_to_left('de'));
        $this->assertFalse(locale_is_right_to_left('zh'));
        $this->assertFalse(locale_is_right_to_left(''));
    }
}

class TestHandler
{
    public static function handleStatic()
    {
    }

    public function handle()
    {
    }
}

class TestHandlerInvokable
{
    public function __invoke()
    {
    }
}
