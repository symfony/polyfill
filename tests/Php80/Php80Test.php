<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php80;

use PHPUnit\Framework\TestCase;

/**
 * @author Ion Bazan <ion.bazan@gmail.com>
 * @author Nico Oelgart <nicoswd@gmail.com>
 *
 * @group legacy
 */
class Php80Test extends TestCase
{
    /**
     * @covers \Symfony\Polyfill\Php80\Php80::fdiv
     * @dataProvider fdivProvider
     */
    public function testFdiv($expected, $divident, $divisor)
    {
        try {
            $result = fdiv($divident, $divisor);
        } catch (\DivisionByZeroError $e) {
            $result = $expected;
        }
        $this->assertSame($expected, $result);
        // Cast to string to detect negative zero "-0"
        $this->assertSame((string) $expected, (string) $result);
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::fdiv
     * @dataProvider nanFdivProvider
     */
    public function testFdivNan($divident, $divisor)
    {
        try {
            $this->assertNan(fdiv($divident, $divisor));
        } catch (\DivisionByZeroError $e) {
            $this->assertNan(\NAN);
        }
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::fdiv
     * @dataProvider invalidFloatProvider
     */
    public function testFdivTypeError($divident, $divisor)
    {
        $this->expectException('TypeError');
        fdiv($divident, $divisor);
    }

    public function testFilterValidateBool()
    {
        $this->assertTrue(\defined('FILTER_VALIDATE_BOOL'));
        $this->assertSame(\FILTER_VALIDATE_BOOLEAN, \FILTER_VALIDATE_BOOL);
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::preg_last_error_msg
     */
    public function testPregNoError()
    {
        $this->assertSame('No error', preg_last_error_msg());
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::preg_last_error_msg
     */
    public function testPregMalformedUtfError()
    {
        @preg_split('/a/u', "a\xff");
        $this->assertSame('Malformed UTF-8 characters, possibly incorrectly encoded', preg_last_error_msg());
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::preg_last_error_msg
     */
    public function testPregMalformedUtf8Offset()
    {
        @preg_match('/a/u', "\xE3\x82\xA2", $m, 0, 1);
        $this->assertSame(
            'The offset did not correspond to the beginning of a valid UTF-8 code point',
            preg_last_error_msg()
        );
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::str_contains
     */
    public function testStrContains()
    {
        $this->assertTrue(str_contains('abc', ''));
        $this->assertTrue(str_contains('abc', null));
        $this->assertTrue(str_contains('abc', 'a'));
        $this->assertTrue(str_contains('abc', 'bc'));
        $this->assertTrue(str_contains('abc', 'abc'));
        $this->assertTrue(str_contains('한국어', '국'));
        $this->assertTrue(str_contains('한국어', ''));
        $this->assertTrue(str_contains('', ''));
        $this->assertFalse(str_contains('abc', 'd'));
        $this->assertFalse(str_contains('', 'd'));
        $this->assertFalse(str_contains(null, 'd'));
        $this->assertFalse(str_contains('abc', 'abcd'));
        $this->assertFalse(str_contains('DÉJÀ', 'à'));
        $this->assertFalse(str_contains('a', 'à'));
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::str_starts_with
     */
    public function testStrStartsWith()
    {
        $testStr = 'beginningMiddleEnd';

        $this->assertTrue(str_starts_with($testStr, 'beginning'));
        $this->assertTrue(str_starts_with($testStr, $testStr));
        $this->assertTrue(str_starts_with($testStr, ''));
        $this->assertTrue(str_starts_with($testStr, null));
        $this->assertTrue(str_starts_with('', ''));
        $this->assertTrue(str_starts_with(null, ''));
        $this->assertTrue(str_starts_with("\x00", ''));
        $this->assertTrue(str_starts_with("\x00", "\x00"));
        $this->assertTrue(str_starts_with("\x00a", "\x00"));
        $this->assertTrue(str_starts_with("a\x00bc", "a\x00b"));

        $this->assertFalse(str_starts_with($testStr, 'Beginning'));
        $this->assertFalse(str_starts_with($testStr, 'eginning'));
        $this->assertFalse(str_starts_with($testStr, $testStr.$testStr));
        $this->assertFalse(str_starts_with('', ' '));
        $this->assertFalse(str_starts_with($testStr, "\x00"));
        $this->assertFalse(str_starts_with("a\x00b", "a\x00d"));
        $this->assertFalse(str_starts_with("a\x00b", "z\x00b"));
        $this->assertFalse(str_starts_with('a', "a\x00"));
        $this->assertFalse(str_starts_with('a', "\x00a"));

        // අයේෂ් = අ + ය + "ේ" + ෂ + ්
        // අයේෂ් = (0xe0 0xb6 0x85) + (0xe0 0xb6 0xba) + (0xe0 0xb7 0x9a) + (0xe0 0xb7 0x82) + (0xe0 0xb7 0x8a)
        $testMultiByte = 'අයේෂ්'; // 0xe0 0xb6 0x85 0xe0 0xb6 0xba 0xe0 0xb7 0x9a 0xe0 0xb7 0x82 0xe0 0xb7 0x8a
        $this->assertTrue(str_starts_with($testMultiByte, 'අයේ')); // 0xe0 0xb6 0x85 0xe0 0xb6 0xba 0xe0 0xb7 0x9a
        $this->assertTrue(str_starts_with($testMultiByte, 'අය')); // 0xe0 0xb6 0x85 0xe0 0xb6 0xba
        $this->assertFalse(str_starts_with($testMultiByte, 'ය')); // 0xe0 0xb6 0xba
        $this->assertFalse(str_starts_with($testMultiByte, 'අේ')); // 0xe0 0xb6 0x85 0xe0 0xb7 0x9a

        $testEmoji = '🙌🎉✨🚀'; // 0xf0 0x9f 0x99 0x8c 0xf0 0x9f 0x8e 0x89 0xe2 0x9c 0xa8 0xf0 0x9f 0x9a 0x80
        $this->assertTrue(str_starts_with($testEmoji, '🙌')); // 0xf0 0x9f 0x99 0x8c
        $this->assertFalse(str_starts_with($testEmoji, '✨')); // 0xe2 0x9c 0xa8
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::str_ends_with
     */
    public function testStrEndsWith()
    {
        $testStr = 'beginningMiddleEnd';

        $this->assertTrue(str_ends_with($testStr, 'End'));
        $this->assertFalse(str_ends_with($testStr, 'end'));
        $this->assertFalse(str_ends_with($testStr, 'en'));
        $this->assertTrue(str_ends_with($testStr, $testStr));
        $this->assertFalse(str_ends_with($testStr, $testStr.$testStr));
        $this->assertTrue(str_ends_with($testStr, ''));
        $this->assertTrue(str_ends_with($testStr, null));
        $this->assertTrue(str_ends_with('', ''));
        $this->assertTrue(str_ends_with(null, ''));
        $this->assertFalse(str_ends_with('', ' '));
        $this->assertFalse(str_ends_with($testStr, "\x00"));
        $this->assertTrue(str_ends_with("\x00", ''));
        $this->assertTrue(str_ends_with("\x00", "\x00"));
        $this->assertTrue(str_ends_with("a\x00", "\x00"));
        $this->assertTrue(str_ends_with("ab\x00c", "b\x00c"));
        $this->assertFalse(str_ends_with("a\x00b", "d\x00b"));
        $this->assertFalse(str_ends_with("a\x00b", "a\x00z"));
        $this->assertFalse(str_ends_with('a', "\x00a"));
        $this->assertFalse(str_ends_with('a', "a\x00"));

        $testMultiByte = 'අයේෂ්'; // 0xe0 0xb6 0x85 0xe0 0xb6 0xba 0xe0 0xb7 0x9a 0xe0 0xb7 0x82 0xe0 0xb7 0x8a
        $this->assertTrue(str_ends_with($testMultiByte, 'ෂ්')); // 0xe0 0xb7 0x82 0xe0 0xb7 0x8a
        $this->assertTrue(str_ends_with($testMultiByte, '්')); // 0xe0 0xb7 0x8a
        $this->assertFalse(str_ends_with($testMultiByte, 'ෂ')); // 0xe0 0xb7 0x82

        $testEmoji = '🙌🎉✨🚀'; // 0xf0 0x9f 0x99 0x8c 0xf0 0x9f 0x8e 0x89 0xe2 0x9c 0xa8 0xf0 0x9f 0x9a 0x80
        $this->assertTrue(str_ends_with($testEmoji, '🚀')); // 0xf0 0x9f 0x9a 0x80
        $this->assertFalse(str_ends_with($testEmoji, '✨')); // 0xe2 0x9c 0xa8

        $this->assertFalse(str_ends_with('', '[]'));
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::get_resource_id
     */
    public function testGetResourceIdWithValidResource()
    {
        $resource = fopen(__FILE__, 'r');
        $resourceId = (int) $resource;
        $this->assertSame($resourceId, get_resource_id($resource));
        fclose($resource);
        $this->assertSame($resourceId, get_resource_id($resource));
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::get_resource_id
     * @dataProvider invalidResourceProvider
     */
    public function testGetResourceWithInvalidValue($value)
    {
        $this->expectException('TypeError');
        get_resource_id($value);
    }

    public static function fdivProvider()
    {
        return [
            [10 / 3, '10', '3'],
            [10 / 3, 10.0, 3.0],
            [-4.0, -10.0, 2.5],
            [-4.0, 10.0, -2.5],
            [\INF, 10.0, 0.0],
            [-\INF, 10.0, -0.0],
            [-\INF, -10.0, 0.0],
            [\INF, -10.0, -0.0],
            [\INF, \INF, 0.0],
            [-\INF, \INF, -0.0],
            [-\INF, -\INF, 0.0],
            [\INF, -\INF, -0.0],
            [0.0, 0.0, \INF],
            [-0.0, 0.0, -\INF],
            [-0.0, -0.0, \INF],
            [0.0, -0.0, -\INF],
        ];
    }

    public static function nanFdivProvider()
    {
        return [
            [0.0, 0.0],
            [0.0, -0.0],
            [-0.0, 0.0],
            [-0.0, -0.0],
            [\INF, \INF],
            [\INF, -\INF],
            [-\INF, \INF],
            [-\INF, -\INF],
            [\NAN, \NAN],
            [\INF, \NAN],
            [-0.0, \NAN],
            [\NAN, \INF],
            [\NAN, 0.0],
        ];
    }

    public static function invalidFloatProvider()
    {
        return [
            ['invalid', 1.0],
            ['invalid', 'invalid'],
            [1.0, 'invalid'],
        ];
    }

    public static function invalidResourceProvider()
    {
        return [
            [true],
            [null],
            [new \stdClass()],
            ['test'],
            [10],
            [10.0],
        ];
    }

    /**
     * @covers \Symfony\Polyfill\Php80\Php80::get_debug_type
     */
    public function testGetDebugType()
    {
        $this->assertSame(__CLASS__, get_debug_type($this));
        $this->assertSame('stdClass', get_debug_type(new \stdClass()));
        $this->assertSame('class@anonymous', get_debug_type(eval('return new class() {};')));
        $this->assertSame('stdClass@anonymous', get_debug_type(eval('return new class() extends stdClass {};')));
        $this->assertSame('Reflector@anonymous', get_debug_type(eval('return new class() implements Reflector { function __toString() {} public static function export() {} };')));

        $this->assertSame('string', get_debug_type('foo'));
        $this->assertSame('bool', get_debug_type(false));
        $this->assertSame('bool', get_debug_type(true));
        $this->assertSame('null', get_debug_type(null));
        $this->assertSame('array', get_debug_type([]));
        $this->assertSame('int', get_debug_type(1));
        $this->assertSame('float', get_debug_type(1.2));
        $this->assertSame('resource (stream)', get_debug_type($h = fopen(__FILE__, 'r')));
        $this->assertSame('resource (closed)', get_debug_type(fclose($h) ? $h : $h));

        $unserializeCallbackHandler = ini_set('unserialize_callback_func', null);
        $var = unserialize('O:8:"Foo\Buzz":0:{}');
        ini_set('unserialize_callback_func', $unserializeCallbackHandler);

        $this->assertSame('__PHP_Incomplete_Class', get_debug_type($var));
    }

    public function testAttributePolyfill()
    {
        $attribute = new \Attribute();
        $this->assertSame(\Attribute::TARGET_ALL, $attribute->flags);
        $attribute = new \Attribute(\Attribute::TARGET_CLASS);
        $this->assertSame(\Attribute::TARGET_CLASS, $attribute->flags);
    }
}
