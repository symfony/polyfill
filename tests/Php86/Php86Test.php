<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php86;

use PHPUnit\Framework\TestCase;

class Php86Test extends TestCase
{
    /**
     * @dataProvider provideValidClampInput
     */
    public function testClampSuccess(array $arguments, $result): void
    {
        [$value, $min, $max] = $arguments;

        $actual = clamp($value, $min, $max);

        if ($value instanceof \DateTimeImmutable) {
            $this->assertInstanceOf(\DateTimeImmutable::class, $actual);

            $actual = $actual->format('Y-m-d');
        }

        $this->assertSame($result, $actual);
    }

    public function testClampNanReturn(): void
    {
        $this->assertNan(clamp(\NAN, 4, 6));
    }

    public static function provideValidClampInput(): array
    {
        $a = new \InvalidArgumentException('a');
        $b = new \RuntimeException('b');
        $c = new \LogicException('c');

        return [
            [
                [2, 1, 3],
                2,
            ],
            [
                [0, 1, 3],
                1,
            ],
            [
                [6, 1, 3],
                3,
            ],
            [
                [2, 1.3, 3.4],
                2,
            ],
            [
                [2.5, 1, 3],
                2.5,
            ],
            [
                [2.5, 1.3, 3.4],
                2.5,
            ],
            [
                [0, 1.3, 3.4],
                1.3,
            ],
            [
                [\M_PI, -\INF, \INF],
                \M_PI,
            ],
            [
                ['a', 'c', 'g'],
                'c',
            ],
            [
                ['d', 'c', 'g'],
                'd',
            ],
            [
                ['2025-08-01', '2025-08-15', '2025-09-15'],
                '2025-08-15',
            ],
            [
                ['2025-08-20', '2025-08-15', '2025-09-15'],
                '2025-08-20',
            ],
            [
                [new \DateTimeImmutable('2025-08-01'), new \DateTimeImmutable('2025-08-15'), new \DateTimeImmutable('2025-09-15')],
                '2025-08-15',
            ],
            [
                [new \DateTimeImmutable('2025-08-20'), new \DateTimeImmutable('2025-08-15'), new \DateTimeImmutable('2025-09-15')],
                '2025-08-20',
            ],
            [
                [null, -1, 1],
                -1,
            ],
            [
                [null, 1, 3],
                1,
            ],
            [
                [null, -3, -1],
                -3,
            ],
            [
                [-9999, null, 10],
                -9999,
            ],
            [
                [12, null, 10],
                10,
            ],
            [
                [$a, $b, $c],
                $a,
            ],
            [
                [$b, $a, $c],
                $b,
            ],
            [
                [$c, $a, $b],
                $c,
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidClampInput
     */
    public function testClampFailure(array $arguments, string $error): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage($error);

        [$value, $min, $max] = $arguments;
        clamp($value, $min, $max);
    }

    public static function provideInvalidClampInput(): array
    {
        return [
            [
                [4, \NAN, 6],
                'clamp(): Argument #2 ($min) must not be NAN',
            ],
            [
                [7, 6, \NAN],
                'clamp(): Argument #3 ($max) must not be NAN',
            ],
            [
                [1, 3, 2],
                'clamp(): Argument #2 ($min) must be smaller than or equal to argument #3 ($max)',
            ],
            [
                [-9999, 5, null],
                'clamp(): Argument #2 ($min) must be smaller than or equal to argument #3 ($max)',
            ],
            [
                [12, -5, null],
                'clamp(): Argument #2 ($min) must be smaller than or equal to argument #3 ($max)',
            ],
        ];
    }

    /**
     * @requires PHP 8.1
     */
    public function testSortDirectionEnum()
    {
        $this->assertTrue(enum_exists(\SortDirection::class), 'SortDirection enum exists');
        $this->assertInstanceOf(\UnitEnum::class, \SortDirection::Ascending);
        $this->assertInstanceOf(\UnitEnum::class, \SortDirection::Descending);
    }

    /**
     * @dataProvider provideGraphemeStrrev
     */
    public function testGraphemeStrrev(string $expected, string $string)
    {
        $this->assertSame($expected, grapheme_strrev($string));
    }

    public static function provideGraphemeStrrev(): array
    {
        return [
            ['', ''],
            ['A', 'A'],
            ['EDCBA', 'ABCDE'],
            ['elppA eplpA', 'Aplpe Apple'],
            ['🍏elppA', 'Apple🍏'],
            ['🇳🇨 - 🇨🇳', '🇨🇳 - 🇳🇨'],
            ['日本語', '語本日'],
            ['🎉👍🏽🎊', '🎊👍🏽🎉'],
            ['C👍🏽A', 'A👍🏽C'],
            ["B\0A", "A\0B"],
        ];
    }

    /**
     * @requires PHP < 8.6
     */
    public function testGraphemeStrrevInvalidUtf8()
    {
        $this->assertFalse(grapheme_strrev("\xFF"));
    }
}
