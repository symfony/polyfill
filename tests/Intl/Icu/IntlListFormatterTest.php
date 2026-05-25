<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Intl\Icu;

use PHPUnit\Framework\TestCase;

/**
 * @author Ayesh Karunaratne <ayesh@aye.sh>
 *
 * @group class-polyfill
 */
class IntlListFormatterTest extends TestCase
{
    public function testSupportedLocales()
    {
        $this->expectNotToPerformAssertions();
        new \IntlListFormatter('en');
        new \IntlListFormatter('en-US');
        new \IntlListFormatter('en_US');
        new \IntlListFormatter('en-LK');
    }

    public function testUnsupportedLocales()
    {
        if (\PHP_VERSION_ID >= 80500) {
            $this->markTestSkipped('Native IntlListFormatter accepts the "ja" locale on PHP 8.5+.');
        }

        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
        } else {
            $this->expectException(\InvalidArgumentException::class);
        }

        new \IntlListFormatter('ja');
    }

    public function testUnsupportedType()
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
        } else {
            $this->expectException(\InvalidArgumentException::class);
        }
        $this->expectExceptionMessage('must be one of IntlListFormatter::TYPE_AND, IntlListFormatter::TYPE_OR, or IntlListFormatter::TYPE_UNITS');
        new \IntlListFormatter('en', 42);
    }

    public function testUnsupportedWidth()
    {
        if (80000 <= \PHP_VERSION_ID) {
            $this->expectException(\ValueError::class);
        } else {
            $this->expectException(\InvalidArgumentException::class);
        }
        $this->expectExceptionMessage('must be one of IntlListFormatter::WIDTH_WIDE, IntlListFormatter::WIDTH_SHORT, or IntlListFormatter::WIDTH_NARROW');
        new \IntlListFormatter('en', \IntlListFormatter::TYPE_AND, 42);
    }

    /**
     * @dataProvider provideFormattingLists
     */
    public function testFormatting(int $type, int $width, array $strings, string $expected)
    {
        $formatter = new \IntlListFormatter('en', $type, $width);
        $this->assertSame($expected, $formatter->format($strings));
    }

    public static function provideFormattingLists()
    {
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_WIDE, [], ''];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_WIDE, [1], '1'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_WIDE, ['1'], '1'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_WIDE, ['apple'], 'apple'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_WIDE, ['apple', 'banana', 'strawberry'], 'apple, banana, and strawberry'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_WIDE, ['apple', 'banana', 'strawberry', 'orange'], 'apple, banana, strawberry, and orange'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_WIDE, ['apple', 'banana', 'strawberry', 'orange', 16], 'apple, banana, strawberry, orange, and 16'];

        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_SHORT, [], ''];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_SHORT, [1], '1'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_SHORT, ['1'], '1'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_SHORT, ['apple'], 'apple'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_SHORT, ['apple', 'banana', 'strawberry'], 'apple, banana, & strawberry'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_SHORT, ['apple', 'banana', 'strawberry', 'orange'], 'apple, banana, strawberry, & orange'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_SHORT, ['apple', 'banana', 'strawberry', 'orange', 16], 'apple, banana, strawberry, orange, & 16'];

        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_NARROW, [], ''];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_NARROW, [1], '1'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_NARROW, ['1'], '1'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_NARROW, ['apple'], 'apple'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_NARROW, ['apple', 'banana', 'strawberry'], 'apple, banana, strawberry'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_NARROW, ['apple', 'banana', 'strawberry', 'orange'], 'apple, banana, strawberry, orange'];
        yield [\IntlListFormatter::TYPE_AND, \IntlListFormatter::WIDTH_NARROW, ['apple', 'banana', 'strawberry', 'orange', 16], 'apple, banana, strawberry, orange, 16'];

        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_WIDE, [], ''];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_WIDE, [1], '1'];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_WIDE, ['1'], '1'];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_WIDE, ['apple'], 'apple'];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_WIDE, ['apple', 'banana', 'strawberry'], 'apple, banana, or strawberry'];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_WIDE, ['apple', 'banana', 'strawberry', 'orange'], 'apple, banana, strawberry, or orange'];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_WIDE, ['apple', 'banana', 'strawberry', 'orange', 16], 'apple, banana, strawberry, orange, or 16'];

        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_SHORT, [], ''];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_SHORT, [1], '1'];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_SHORT, ['1'], '1'];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_SHORT, ['apple'], 'apple'];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_SHORT, ['apple', 'banana', 'strawberry'], 'apple, banana, or strawberry'];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_SHORT, ['apple', 'banana', 'strawberry', 'orange'], 'apple, banana, strawberry, or orange'];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_SHORT, ['apple', 'banana', 'strawberry', 'orange', 16], 'apple, banana, strawberry, orange, or 16'];

        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_NARROW, [], ''];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_NARROW, [1], '1'];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_NARROW, ['1'], '1'];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_NARROW, ['apple'], 'apple'];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_NARROW, ['apple', 'banana', 'strawberry', 'orange'], 'apple, banana, strawberry, or orange'];
        yield [\IntlListFormatter::TYPE_OR, \IntlListFormatter::WIDTH_NARROW, ['apple', 'banana', 'strawberry', 'orange', 16], 'apple, banana, strawberry, orange, or 16'];

        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_WIDE, [], ''];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_WIDE, [1], '1'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_WIDE, ['1'], '1'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_WIDE, ['apple'], 'apple'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_WIDE, ['apple', 'banana', 'strawberry'], 'apple, banana, strawberry'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_WIDE, ['apple', 'banana', 'strawberry', 'orange'], 'apple, banana, strawberry, orange'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_WIDE, ['apple', 'banana', 'strawberry', 'orange', 16], 'apple, banana, strawberry, orange, 16'];

        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_SHORT, [], ''];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_SHORT, [1], '1'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_SHORT, ['1'], '1'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_SHORT, ['apple'], 'apple'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_SHORT, ['apple', 'banana', 'strawberry'], 'apple, banana, strawberry'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_SHORT, ['apple', 'banana', 'strawberry', 'orange'], 'apple, banana, strawberry, orange'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_SHORT, ['apple', 'banana', 'strawberry', 'orange', 16], 'apple, banana, strawberry, orange, 16'];

        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_NARROW, [], ''];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_NARROW, [1], '1'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_NARROW, ['1'], '1'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_NARROW, ['apple'], 'apple'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_NARROW, ['apple', 'banana', 'strawberry'], 'apple banana strawberry'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_NARROW, ['apple', 'banana', 'strawberry', 'orange'], 'apple banana strawberry orange'];
        yield [\IntlListFormatter::TYPE_UNITS, \IntlListFormatter::WIDTH_NARROW, ['apple', 'banana', 'strawberry', 'orange', 16], 'apple banana strawberry orange 16'];
    }
}
