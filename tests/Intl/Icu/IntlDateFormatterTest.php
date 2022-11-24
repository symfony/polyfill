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
use Symfony\Polyfill\Intl\Icu\IntlDateFormatter;

/**
 * @group class-polyfill
 * @group time-sensitive
 */
class IntlDateFormatterTest extends AbstractIntlDateFormatterTest
{
    public function testConstructor()
    {
        $formatter = $this->getDateFormatter('en', IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT, 'UTC', IntlDateFormatter::GREGORIAN, 'y-M-d');
        $this->assertEquals('y-M-d', $formatter->getPattern());
    }

    public function testConstructorWithoutLocale()
    {
        $formatter = $this->getDateFormatter(null, IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT, 'UTC', IntlDateFormatter::GREGORIAN, 'y-M-d');
        $this->assertEquals('y-M-d', $formatter->getPattern());
    }

    public function testConstructorWithoutCalendar()
    {
        $formatter = $this->getDateFormatter('en', IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT, 'UTC', null, 'y-M-d');
        $this->assertEquals('y-M-d', $formatter->getPattern());
    }

    public function testConstructorWithUnsupportedLocale()
    {
        $this->expectException(MethodArgumentValueNotImplementedException::class);
        $this->getDateFormatter('pt_BR', IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
    }

    public function testStaticCreate()
    {
        $formatter = $this->getDateFormatter('en', IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
        $formatter = $formatter::create('en', IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
        $this->assertInstanceOf(IntlDateFormatter::class, $formatter);
    }

    public function testFormatWithUnsupportedTimestampArgument()
    {
        $formatter = $this->getDefaultDateFormatter();

        $localtime = [
            'tm_sec' => 59,
            'tm_min' => 3,
            'tm_hour' => 15,
            'tm_mday' => 15,
            'tm_mon' => 3,
            'tm_year' => 112,
            'tm_wday' => 0,
            'tm_yday' => 105,
            'tm_isdst' => 0,
        ];

        try {
            $formatter->format($localtime);
        } catch (\Exception $e) {
            $this->assertInstanceOf(MethodArgumentValueNotImplementedException::class, $e);
            $this->assertStringEndsWith('Only Unix timestamps and DateTime objects are supported.  Please install the "intl" extension for full localization capabilities.', $e->getMessage());
        }
    }

    public function testFormatWithUnimplementedChars()
    {
        $this->expectException(NotImplementedException::class);
        $pattern = 'Y';
        $formatter = $this->getDateFormatter('en', IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT, 'UTC', IntlDateFormatter::GREGORIAN, $pattern);
        $formatter->format(0);
    }

    public function testFormatWithNonIntegerTimestamp()
    {
        $this->expectException(NotImplementedException::class);
        $formatter = $this->getDefaultDateFormatter();
        $formatter->format([]);
    }

    public function testGetErrorCode()
    {
        $formatter = $this->getDefaultDateFormatter();
        $this->assertEquals(Icu::getErrorCode(), $formatter->getErrorCode());
    }

    public function testGetErrorMessage()
    {
        $formatter = $this->getDefaultDateFormatter();
        $this->assertEquals(Icu::getErrorMessage(), $formatter->getErrorMessage());
    }

    public function testIsLenient()
    {
        $formatter = $this->getDefaultDateFormatter();
        $this->assertFalse($formatter->isLenient());
    }

    public function testLocaltime()
    {
        $this->expectException(MethodNotImplementedException::class);
        $formatter = $this->getDefaultDateFormatter();
        $formatter->localtime('Wednesday, December 31, 1969 4:00:00 PM PT');
    }

    public function testParseWithNotNullPositionValue()
    {
        $this->expectException(MethodArgumentNotImplementedException::class);
        $position = 0;
        $formatter = $this->getDefaultDateFormatter('y');
        $this->assertSame(0, $formatter->parse('1970', $position));
    }

    public function testSetCalendar()
    {
        $this->expectException(MethodNotImplementedException::class);
        $formatter = $this->getDefaultDateFormatter();
        $formatter->setCalendar(IntlDateFormatter::GREGORIAN);
    }

    public function testSetLenient()
    {
        $this->expectException(MethodArgumentValueNotImplementedException::class);
        $formatter = $this->getDefaultDateFormatter();
        $formatter->setLenient(true);
    }

    public function testFormatWithGmtTimeZoneAndMinutesOffset()
    {
        $this->expectException(NotImplementedException::class);
        parent::testFormatWithGmtTimeZoneAndMinutesOffset();
    }

    public function testFormatWithNonStandardTimezone()
    {
        $this->expectException(NotImplementedException::class);
        parent::testFormatWithNonStandardTimezone();
    }

    public static function parseStandaloneAmPmProvider()
    {
        return self::notImplemented(parent::parseStandaloneAmPmProvider());
    }

    public static function parseDayOfWeekProvider()
    {
        return self::notImplemented(parent::parseDayOfWeekProvider());
    }

    public static function parseDayOfYearProvider()
    {
        return self::notImplemented(parent::parseDayOfYearProvider());
    }

    public static function parseQuarterProvider()
    {
        return self::notImplemented(parent::parseQuarterProvider());
    }

    public function testParseThreeDigitsYears()
    {
        if (\PHP_INT_SIZE < 8) {
            $this->markTestSkipped('Parsing three digits years requires a 64bit PHP.');
        }

        $formatter = $this->getDefaultDateFormatter('yyyy-M-d');
        $this->assertSame(-32157648000, $formatter->parse('950-12-19'));
        $this->assertIsIntlSuccess($formatter, 'U_ZERO_ERROR', Icu::U_ZERO_ERROR);
    }

    protected function getDateFormatter($locale, $datetype, $timetype, $timezone = null, $calendar = IntlDateFormatter::GREGORIAN, $pattern = null)
    {
        return new class($locale, $datetype, $timetype, $timezone, $calendar, $pattern) extends IntlDateFormatter {
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

    /**
     * Just to document the differences between the stub and the intl
     * implementations. The intl can parse any of the tested formats alone. The
     * stub does not implement them as it would be needed to add more
     * abstraction, passing more context to the transformers objects. Any of the
     * formats are ignored alone or with date/time data (years, months, days,
     * hours, minutes and seconds).
     *
     * Also in intl, format like 'ss E' for '10 2' (2nd day of year
     * + 10 seconds) are added, then we have 86,400 seconds (24h * 60min * 60s)
     * + 10 seconds
     */
    private static function notImplemented(array $dataSets): array
    {
        return array_map(function (array $row) {
            return [$row[0], $row[1], 0];
        }, $dataSets);
    }
}
