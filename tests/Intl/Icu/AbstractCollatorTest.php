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
use Symfony\Polyfill\Intl\Icu\Collator;

/**
 * Test case for Collator implementations.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractCollatorTest extends TestCase
{
    /**
     * @dataProvider asortProvider
     */
    public function testAsort($array, $sortFlag, $expected)
    {
        $collator = $this->getCollator('en');
        $collator->asort($array, $sortFlag);
        $this->assertSame($expected, $array);
    }

    public static function asortProvider()
    {
        return [
            /* array, sortFlag, expected */
            [
                ['a', 'b', 'c'],
                Collator::SORT_REGULAR,
                ['a', 'b', 'c'],
            ],
            [
                ['c', 'b', 'a'],
                Collator::SORT_REGULAR,
                [2 => 'a', 1 => 'b',  0 => 'c'],
            ],
            [
                ['b', 'c', 'a'],
                Collator::SORT_REGULAR,
                [2 => 'a', 0 => 'b', 1 => 'c'],
            ],
            [
                ['b', 'B', 'a', 'A'],
                Collator::SORT_REGULAR,
                [2 => 'a', 3 => 'A', 0 => 'b', 1 => 'B'],
            ],
            [
                ['x10', 'x9', '10', '9'],
                Collator::SORT_REGULAR,
                [3 => '9', 2 => '10', 0 => 'x10', 1 => 'x9'],
            ],
            [
                ['x10', 'x9', '10', '9'],
                Collator::SORT_STRING,
                [2 => '10', 3 => '9', 0 => 'x10', 1 => 'x9'],
            ],
            [
                ['10', '9', '1.5'],
                Collator::SORT_NUMERIC,
                [2 => '1.5', 1 => '9', 0 => '10'],
            ],
        ];
    }

    /**
     * @dataProvider asortProvider
     */
    public function testSort($array, $sortFlag, $expected)
    {
        $collator = $this->getCollator('en');
        $collator->sort($array, $sortFlag);
        $this->assertSame(array_values($expected), $array);
    }

    public function testSortStringableObjectsAsStrings()
    {
        $a = new \SplFileInfo('a');
        $b = new \SplFileInfo('b');
        $array = [$b, 'B', $a, 'A'];

        $collator = $this->getCollator('en');
        $collator->sort($array);
        $this->assertSame([$a, 'A', $b, 'B'], $array);
    }

    public function testCompareReturnsMinusOneZeroOrOne()
    {
        $collator = $this->getCollator('en');
        $this->assertSame(-1, $collator->compare('a', 'z'));
        $this->assertSame(0, $collator->compare('a', 'a'));
        $this->assertSame(1, $collator->compare('z', 'a'));
    }

    /**
     * @return Collator|\Collator
     */
    abstract protected function getCollator(string $locale);
}
