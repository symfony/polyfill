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

use Symfony\Polyfill\Intl\Icu\Collator;
use Symfony\Polyfill\Intl\Icu\Exception\MethodArgumentValueNotImplementedException;
use Symfony\Polyfill\Intl\Icu\Exception\MethodNotImplementedException;
use Symfony\Polyfill\Intl\Icu\Icu;

/**
 * @group class-polyfill
 */
class CollatorTest extends AbstractCollatorTest
{
    public function testConstructorWithUnsupportedLocale()
    {
        $this->expectException(MethodArgumentValueNotImplementedException::class);
        $this->getCollator('pt_BR');
    }

    public function testCompare()
    {
        $collator = $this->getCollator('en');
        $this->assertEquals(-1, $collator->compare('a', 'b'));
        $this->assertEquals(0, $collator->compare('a', 'a'));
        $this->assertEquals(1, $collator->compare('b', 'a'));

        // case-insensitive base ordering
        $this->assertLessThan(0, $collator->compare('a', 'B'));
        $this->assertLessThan(0, $collator->compare('A', 'b'));
        $this->assertGreaterThan(0, $collator->compare('Z', 'a'));

        // lowercase before uppercase tie-breaking
        $this->assertLessThan(0, $collator->compare('a', 'A'));
        $this->assertGreaterThan(0, $collator->compare('ABC', 'abc'));
    }

    public function testGetAttribute()
    {
        $this->expectException(MethodNotImplementedException::class);
        $collator = $this->getCollator('en');
        $collator->getAttribute(Collator::NUMERIC_COLLATION);
    }

    public function testGetErrorCode()
    {
        $collator = $this->getCollator('en');
        $this->assertEquals(Icu::U_ZERO_ERROR, $collator->getErrorCode());
    }

    public function testGetErrorMessage()
    {
        $collator = $this->getCollator('en');
        $this->assertEquals('U_ZERO_ERROR', $collator->getErrorMessage());
    }

    public function testGetLocale()
    {
        $collator = $this->getCollator('en');
        $this->assertEquals('en', $collator->getLocale());
    }

    public function testConstructWithoutLocale()
    {
        $collator = $this->getCollator(null);
        $this->assertInstanceOf(Collator::class, $collator);
    }

    public function testGetSortKey()
    {
        $this->expectException(MethodNotImplementedException::class);
        $collator = $this->getCollator('en');
        $collator->getSortKey('Hello');
    }

    public function testGetStrength()
    {
        $this->expectException(MethodNotImplementedException::class);
        $collator = $this->getCollator('en');
        $collator->getStrength();
    }

    public function testSetAttribute()
    {
        $this->expectException(MethodNotImplementedException::class);
        $collator = $this->getCollator('en');
        $collator->setAttribute(Collator::NUMERIC_COLLATION, Collator::ON);
    }

    public function testSetStrength()
    {
        $this->expectException(MethodNotImplementedException::class);
        $collator = $this->getCollator('en');
        $collator->setStrength(Collator::PRIMARY);
    }

    public function testStaticCreate()
    {
        $collator = $this->getCollator('en');
        $collator = $collator::create('en');
        $this->assertInstanceOf(Collator::class, $collator);
    }

    protected function getCollator(?string $locale): Collator
    {
        return new class($locale) extends Collator {
        };
    }
}
