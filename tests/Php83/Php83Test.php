<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php83;

use PHPUnit\Framework\TestCase;

class Php83Test extends TestCase
{
    /**
     * @covers \Symfony\Polyfill\Php83\Php83::json_validate
     * @dataProvider jsonDataProvider
     */
    public function testJsonValidate(bool $valid, string $json, string $errorMessage = 'No error', int $depth = 512, int $options = 0)
    {
        $this->assertSame($valid, json_validate($json, $depth, $options));
        $this->assertSame($errorMessage, json_last_error_msg());
    }

    /**
     * @return iterable<array{0: bool, 1: string, 2?: string, 3?: int, 4?: int}>
     */
    public function jsonDataProvider(): iterable
    {
        yield [false, '', 'Syntax error'];
        yield [false, '.', 'Syntax error'];
        yield [false, '<?>', 'Syntax error'];
        yield [false, ';', 'Syntax error'];
        yield [false, 'руссиш', 'Syntax error'];
        yield [false, 'blah', 'Syntax error'];
        yield [false, '{ "": "": "" } }', 'Syntax error'];
        yield [false, '{ "test": {} "foo": "bar" }, "test2": {"foo" : "bar" }, "test2": {"foo" : "bar" } }', 'Syntax error'];
        yield [true, '{ "test": { "foo": "bar" } }'];
        yield [true, '{ "test": { "foo": "" } }'];
        yield [true, '{ "": { "foo": "" } }'];
        yield [true, '{ "": { "": "" } }'];
        yield [true, '{ "test": {"foo": "bar"}, "test2": {"foo" : "bar" }, "test2": {"foo" : "bar" } }'];
        yield [true, '{ "test": {"foo": "bar"}, "test2": {"foo" : "bar" }, "test3": {"foo" : "bar" } }'];
        yield [false, '{"key1":"value1", "key2":"value2"}', 'Maximum stack depth exceeded', 1];
        yield [false, "\"a\xb0b\"", 'Malformed UTF-8 characters, possibly incorrectly encoded'];

        if (\defined('JSON_INVALID_UTF8_IGNORE')) {
            yield [true, "\"a\xb0b\"", 'No error', 512, \JSON_INVALID_UTF8_IGNORE];
        } else {
            // The $options should not be validated when JSON_INVALID_UTF8_IGNORE is not defined (PHP 7.1)
            yield [true, '{}', 'No error', 512, 1];
        }
    }

    /**
     * @covers \Symfony\Polyfill\Php83\Php83::json_validate
     *
     * @dataProvider invalidOptionsProvider
     */
    public function testInvalidOptionsProvided(int $depth, int $flags, string $expectedError)
    {
        $this->expectException(\ValueError::class);
        $this->expectErrorMessage($expectedError);
        json_validate('{}', $depth, $flags);
    }

    /**
     * @return iterable<array{0: int, 1: int, 2: string}>
     */
    public function invalidOptionsProvider(): iterable
    {
        yield [0, 0, 'json_validate(): Argument #2 ($depth) must be greater than 0'];
        yield [\PHP_INT_MAX, 0, 'json_validate(): Argument #2 ($depth) must be less than 2147483647'];

        if (\defined('JSON_INVALID_UTF8_IGNORE')) {
            yield [
                512,
                \JSON_BIGINT_AS_STRING,
                'json_validate(): Argument #3 ($flags) must be a valid flag (allowed flags: JSON_INVALID_UTF8_IGNORE)',
            ];
        }
    }
}
