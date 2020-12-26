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

use Symfony\Polyfill\Intl\Icu\Exception\MethodArgumentNotImplementedException;
use Symfony\Polyfill\Intl\Icu\Exception\MethodArgumentValueNotImplementedException;
use Symfony\Polyfill\Intl\Icu\Exception\MethodNotImplementedException;
use Symfony\Polyfill\Intl\Icu\Exception\NotImplementedException;
use Symfony\Polyfill\Intl\Icu\Icu;
use Symfony\Polyfill\Intl\Icu\NumberFormatter;

/**
 * Note that there are some values written like -2147483647 - 1. This is the lower 32bit int max and is a known
 * behavior of PHP.
 *
 * @group class-polyfill
 */
class NumberFormatterTest extends AbstractNumberFormatterTest
{
    public function testConstructorWithUnsupportedLocale()
    {
        $this->expectException(MethodArgumentValueNotImplementedException::class);
        $this->getNumberFormatter('pt_BR');
    }

    public function testConstructorWithUnsupportedStyle()
    {
        $this->expectException(MethodArgumentValueNotImplementedException::class);
        $this->getNumberFormatter('en', NumberFormatter::PATTERN_DECIMAL);
    }

    public function testConstructorWithPatternDifferentThanNull()
    {
        $this->expectException(MethodArgumentNotImplementedException::class);
        $this->getNumberFormatter('en', NumberFormatter::DECIMAL, '');
    }

    public function testSetAttributeWithUnsupportedAttribute()
    {
        $this->expectException(MethodArgumentValueNotImplementedException::class);
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::LENIENT_PARSE, 100);
    }

    public function testSetAttributeInvalidRoundingMode()
    {
        $this->expectException(MethodArgumentValueNotImplementedException::class);
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, -1);
    }

    public function testConstructWithoutLocale()
    {
        $this->assertInstanceOf(NumberFormatter::class, $this->getNumberFormatter(null, NumberFormatter::DECIMAL));
    }

    public function testCreate()
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $this->assertInstanceOf(NumberFormatter::class, $formatter::create('en', NumberFormatter::DECIMAL));
    }

    public function testFormatWithCurrencyStyle()
    {
        $this->expectException('RuntimeException');
        parent::testFormatWithCurrencyStyle();
    }

    /**
     * @dataProvider formatTypeInt32Provider
     */
    public function testFormatTypeInt32($formatter, $value, $expected, $message = '')
    {
        $this->expectException(MethodArgumentValueNotImplementedException::class);
        parent::testFormatTypeInt32($formatter, $value, $expected, $message);
    }

    /**
     * @dataProvider formatTypeInt32WithCurrencyStyleProvider
     */
    public function testFormatTypeInt32WithCurrencyStyle($formatter, $value, $expected, $message = '')
    {
        $this->expectException(NotImplementedException::class);
        parent::testFormatTypeInt32WithCurrencyStyle($formatter, $value, $expected, $message);
    }

    /**
     * @dataProvider formatTypeInt64Provider
     */
    public function testFormatTypeInt64($formatter, $value, $expected)
    {
        $this->expectException(MethodArgumentValueNotImplementedException::class);
        parent::testFormatTypeInt64($formatter, $value, $expected);
    }

    /**
     * @dataProvider formatTypeInt64WithCurrencyStyleProvider
     */
    public function testFormatTypeInt64WithCurrencyStyle($formatter, $value, $expected)
    {
        $this->expectException(NotImplementedException::class);
        parent::testFormatTypeInt64WithCurrencyStyle($formatter, $value, $expected);
    }

    /**
     * @dataProvider formatTypeDoubleProvider
     */
    public function testFormatTypeDouble($formatter, $value, $expected)
    {
        $this->expectException(MethodArgumentValueNotImplementedException::class);
        parent::testFormatTypeDouble($formatter, $value, $expected);
    }

    /**
     * @dataProvider formatTypeDoubleWithCurrencyStyleProvider
     */
    public function testFormatTypeDoubleWithCurrencyStyle($formatter, $value, $expected)
    {
        $this->expectException(NotImplementedException::class);
        parent::testFormatTypeDoubleWithCurrencyStyle($formatter, $value, $expected);
    }

    public function testGetPattern()
    {
        $this->expectException(MethodNotImplementedException::class);
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->getPattern();
    }

    public function testGetErrorCode()
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $this->assertEquals(Icu::U_ZERO_ERROR, $formatter->getErrorCode());
    }

    public function testParseCurrency()
    {
        $this->expectException(MethodNotImplementedException::class);
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $currency = 'USD';
        $formatter->parseCurrency(3, $currency);
    }

    public function testSetPattern()
    {
        $this->expectException(MethodNotImplementedException::class);
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->setPattern('#0');
    }

    public function testSetSymbol()
    {
        $this->expectException(MethodNotImplementedException::class);
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->setSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, '*');
    }

    public function testSetTextAttribute()
    {
        $this->expectException(MethodNotImplementedException::class);
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->setTextAttribute(NumberFormatter::NEGATIVE_PREFIX, '-');
    }

    protected function getNumberFormatter(?string $locale = 'en', string $style = null, string $pattern = null): NumberFormatter
    {
        return new class($locale, $style, $pattern) extends NumberFormatter {
        };
    }

    protected function getIntlErrorMessage(): string
    {
        return Icu::getErrorMessage();
    }

    protected function getIntlErrorCode(): int
    {
        return Icu::getErrorCode();
    }

    protected function isIntlFailure($errorCode): bool
    {
        return Icu::isFailure($errorCode);
    }
}
