<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Time;

use PHPUnit\Framework\TestCase;
use Time\Duration;
use Time\TimeException;

/**
 * These tests run against the native implementation as soon as PHP provides the
 * Time extension, so that any divergence of the polyfill is caught.
 *
 * @requires PHP >= 8.1
 */
class DurationTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(Duration::class)) {
            $this->markTestSkipped('The Time extension is not available and the polyfill is disabled on PHP >= 8.6.');
        }
    }

    public function testFromSeconds()
    {
        $duration = Duration::fromSeconds(12, 345);

        $this->assertSame(12, $duration->seconds);
        $this->assertSame(345, $duration->nanoseconds);
        $this->assertFalse($duration->negative);
    }

    /**
     * @dataProvider provideInvalidSeconds
     */
    public function testFromSecondsRejectsInvalidSeconds(int $seconds, string $expectedException)
    {
        $this->expectException($expectedException);

        Duration::fromSeconds($seconds);
    }

    public static function provideInvalidSeconds(): array
    {
        $cases = ['negative value' => [-1, \ValueError::class]];

        if (8 <= \PHP_INT_SIZE) {
            $cases['too large value'] = [9223372036, TimeException::class];
        }

        return $cases;
    }

    /**
     * @dataProvider provideInvalidNanoseconds
     */
    public function testFromSecondsRejectsInvalidNanoseconds(int $nanoseconds)
    {
        $this->expectException(\ValueError::class);

        Duration::fromSeconds(1, $nanoseconds);
    }

    public static function provideInvalidNanoseconds(): array
    {
        return [
            'negative value' => [-1],
            'too large value' => [1000000000],
        ];
    }

    public function testFromNanoseconds()
    {
        $duration = Duration::fromNanoseconds(3500000123);

        $this->assertSame(3, $duration->seconds);
        $this->assertSame(500000123, $duration->nanoseconds);
    }

    public function testFromMicroseconds()
    {
        $duration = Duration::fromMicroseconds(2500001);

        $this->assertSame(2, $duration->seconds);
        $this->assertSame(500001000, $duration->nanoseconds);
    }

    public function testFromMilliseconds()
    {
        $duration = Duration::fromMilliseconds(1500);

        $this->assertSame(1, $duration->seconds);
        $this->assertSame(500000000, $duration->nanoseconds);
    }

    public function testFromMinutes()
    {
        $this->assertSame(120, Duration::fromMinutes(2)->seconds);
    }

    public function testFromHours()
    {
        $this->assertSame(7200, Duration::fromHours(2)->seconds);
    }

    /**
     * @dataProvider provideOutOfRangeFactories
     */
    public function testFactoriesRejectOutOfRangeValues(string $method, int $value)
    {
        if (8 > \PHP_INT_SIZE) {
            $this->markTestSkipped('These values are out of range on 32 bit platforms only.');
        }

        $this->expectException(TimeException::class);

        Duration::$method($value);
    }

    public static function provideOutOfRangeFactories(): array
    {
        return [
            'nanoseconds' => ['fromNanoseconds', \PHP_INT_MAX],
            'microseconds' => ['fromMicroseconds', \PHP_INT_MAX],
            'microseconds just above the limit' => ['fromMicroseconds', 9223372036000000],
            'milliseconds' => ['fromMilliseconds', \PHP_INT_MAX],
            'minutes' => ['fromMinutes', \PHP_INT_MAX],
            'hours' => ['fromHours', \PHP_INT_MAX],
        ];
    }

    /**
     * @dataProvider provideValidIso8601
     */
    public function testParseIso8601(string $specification, int $seconds)
    {
        $duration = Duration::fromIso8601DurationString($specification);

        $this->assertSame($seconds, $duration->seconds);
        $this->assertSame(0, $duration->nanoseconds);
        $this->assertFalse($duration->negative);
    }

    public static function provideValidIso8601(): array
    {
        return [
            '1 second' => ['PT1S', 1],
            '2 minutes 30 seconds' => ['PT2M30S', 150],
            'zero seconds' => ['PT0S', 0],
            'zero minutes' => ['PT0M', 0],
            'zero hours' => ['PT0H', 0],
            'hours are the biggest component' => ['PT1H1M1S', 3661],
            'components are not normalized' => ['PT1H60M60S', 7260],
        ];
    }

    /**
     * @dataProvider provideInvalidIso8601
     */
    public function testRejectsInvalidIso8601(string $specification)
    {
        $this->expectException(TimeException::class);

        Duration::fromIso8601DurationString($specification);
    }

    public static function provideInvalidIso8601(): array
    {
        return [
            'empty string' => [''],
            'period only' => ['P'],
            'time designator only' => ['PT'],
            'not an ISO 8601 duration' => ['foo'],
            'invalid prefix' => ['1H'],
            'missing time designator' => ['P1H'],
            'unsupported years component' => ['P1Y'],
            'unsupported months component' => ['P1M'],
            'unsupported weeks component' => ['P1W'],
            'unsupported days component' => ['P1D'],
            'days with a time component' => ['P1DT0S'],
            'unknown time unit' => ['PT1X'],
            'trailing unit without value' => ['PT1HS'],
            'fractional seconds (dot)' => ['PT1.5S'],
            'fractional seconds (comma)' => ['PT1,5S'],
            'duplicate seconds component' => ['PT1S2S'],
            'negative duration' => ['-PT5S'],
            'date and time' => ['2000-01-01T00:00:00Z'],
            'interval' => ['2000-01-01T00:00:00Z/PT1H'],
            'hours component out of range' => ['PT2562047789H'],
            'combined components out of range' => ['PT2562047788H59M59S'],
        ];
    }

    public function testNegate()
    {
        $duration = Duration::fromSeconds(10)->negate();

        $this->assertTrue($duration->negative);
        $this->assertSame(10, $duration->seconds);
        $this->assertFalse($duration->negate()->negative);
    }

    public function testNegateZeroStaysPositive()
    {
        $this->assertFalse(Duration::fromSeconds(0)->negate()->negative);
    }

    public function testAbsolute()
    {
        $duration = Duration::fromSeconds(10)->negate()->absolute();

        $this->assertFalse($duration->negative);
        $this->assertSame(10, $duration->seconds);
    }

    public function testAddition()
    {
        $result = Duration::fromSeconds(2, 900000000)
            ->add(Duration::fromSeconds(1, 200000000));

        $this->assertSame(4, $result->seconds);
        $this->assertSame(100000000, $result->nanoseconds);
    }

    public function testAdditionWithNegativeOperand()
    {
        $result = Duration::fromSeconds(10)->add(Duration::fromSeconds(3)->negate());

        $this->assertSame(7, $result->seconds);
        $this->assertFalse($result->negative);
    }

    public function testSubtraction()
    {
        $result = Duration::fromSeconds(10)->sub(Duration::fromSeconds(3));

        $this->assertSame(7, $result->seconds);
        $this->assertFalse($result->negative);
    }

    public function testSubtractionBorrow()
    {
        $result = Duration::fromSeconds(5)->sub(Duration::fromSeconds(2, 500000000));

        $this->assertSame(2, $result->seconds);
        $this->assertSame(500000000, $result->nanoseconds);
    }

    public function testSubtractionProducesNegativeDuration()
    {
        $result = Duration::fromSeconds(2)->sub(Duration::fromSeconds(5));

        $this->assertSame(3, $result->seconds);
        $this->assertTrue($result->negative);
    }

    public function testMultiplyBy()
    {
        $result = Duration::fromSeconds(1, 600000000)->multiplyBy(3);

        $this->assertSame(4, $result->seconds);
        $this->assertSame(800000000, $result->nanoseconds);
    }

    public function testMultiplyByZeroIsNeverNegative()
    {
        $result = Duration::fromSeconds(1)->negate()->multiplyBy(0);

        $this->assertSame(0, $result->seconds);
        $this->assertFalse($result->negative);
    }

    public function testMultiplyByRejectsNegativeFactor()
    {
        $this->expectException(\ValueError::class);

        Duration::fromSeconds(1)->multiplyBy(-1);
    }

    public function testDivideBy()
    {
        $result = Duration::fromSeconds(5)->divideBy(2);

        $this->assertSame(2, $result->seconds);
        $this->assertSame(500000000, $result->nanoseconds);
    }

    public function testDivideByTruncatesFractionalNanoseconds()
    {
        $result = Duration::fromSeconds(0, 5)->divideBy(2);

        $this->assertSame(0, $result->seconds);
        $this->assertSame(2, $result->nanoseconds);
    }

    public function testDivideByTruncatingToZeroIsNeverNegative()
    {
        $result = Duration::fromSeconds(0, 1)->negate()->divideBy(2);

        $this->assertSame(0, $result->seconds);
        $this->assertSame(0, $result->nanoseconds);
        $this->assertFalse($result->negative);
    }

    public function testDivideByZero()
    {
        $this->expectException(\DivisionByZeroError::class);

        Duration::fromSeconds(1)->divideBy(0);
    }

    public function testDivideByRejectsNegativeDivisor()
    {
        $this->expectException(\ValueError::class);

        Duration::fromSeconds(1)->divideBy(-1);
    }

    /**
     * @dataProvider provideOverflowingOperations
     */
    public function testArithmeticRejectsOutOfRangeResults(\Closure $operation)
    {
        if (8 > \PHP_INT_SIZE) {
            $this->markTestSkipped('These values are out of range on 32 bit platforms only.');
        }

        $this->expectException(TimeException::class);

        $operation();
    }

    public static function provideOverflowingOperations(): array
    {
        if (8 > \PHP_INT_SIZE) {
            return ['skipped on 32 bit platforms' => [static function () {}]];
        }

        $max = static function () { return Duration::fromSeconds(9223372035, 999999999); };

        return [
            'add' => [static function () use ($max) { return $max()->add(Duration::fromSeconds(0, 1)); }],
            'sub' => [static function () use ($max) { return $max()->sub(Duration::fromSeconds(0, 1)->negate()); }],
            'multiplyBy' => [static function () use ($max) { return $max()->multiplyBy(2); }],
            'multiplyBy nanoseconds only' => [static function () { return Duration::fromSeconds(0, 1)->multiplyBy(9223372036000000000); }],
        ];
    }

    /**
     * The digit-wise division is only reached on 32 bit platforms, where the dividend
     * does not fit in an integer. Check it here against the integer arithmetic of a
     * 64 bit platform, since no CI job runs on 32 bit.
     */
    public function testDivideNanosecondsMatchesIntegerArithmetic()
    {
        if ((new \ReflectionClass(Duration::class))->isInternal()) {
            $this->markTestSkipped('This checks an implementation detail of the polyfill.');
        }
        if (8 > \PHP_INT_SIZE) {
            $this->markTestSkipped('The expected values are computed with 64 bit integers.');
        }

        $divideNanoseconds = new \ReflectionMethod(Duration::class, 'divideNanoseconds');

        foreach ([2, 3, 7, 10, 999999999, 1000000000, 1073741823, 2147483647] as $divisor) {
            foreach ([0, 1, 2, 3, intdiv($divisor, 2), $divisor - 1] as $remainder) {
                foreach ([0, 1, 5, 999999998, 999999999] as $nanoseconds) {
                    $this->assertSame(
                        intdiv($remainder * 1000000000 + $nanoseconds, $divisor),
                        $divideNanoseconds->invoke(null, $remainder, $nanoseconds, $divisor),
                        "intdiv($remainder * 1e9 + $nanoseconds, $divisor)"
                    );
                }
            }
        }
    }

    public function testCompare()
    {
        $this->assertSame(0, Duration::compare(Duration::fromSeconds(1), Duration::fromSeconds(1)));
        $this->assertSame(-1, Duration::compare(Duration::fromSeconds(1), Duration::fromSeconds(2)));
        $this->assertSame(1, Duration::compare(Duration::fromSeconds(2), Duration::fromSeconds(1)));
        $this->assertSame(-1, Duration::compare(Duration::fromSeconds(1, 0), Duration::fromSeconds(1, 1)));
    }

    public function testCompareNegativeDurations()
    {
        $this->assertSame(-1, Duration::compare(
            Duration::fromSeconds(2)->negate(),
            Duration::fromSeconds(1)->negate()
        ));

        $this->assertSame(-1, Duration::compare(
            Duration::fromSeconds(1)->negate(),
            Duration::fromSeconds(1)
        ));
    }

    public function testTheConstructorIsPrivate()
    {
        $this->expectException(\Error::class);

        new Duration();
    }

    public function testTheConstructorIsNotReachableThroughReflection()
    {
        $this->expectException(\ReflectionException::class);

        (new \ReflectionClass(Duration::class))->newInstance();
    }

    public function testDynamicPropertiesAreForbidden()
    {
        $duration = Duration::fromSeconds(1);

        $this->expectException(\Error::class);

        $duration->undefinedProperty = 1;
    }

    /**
     * The readonly.phpt of php-src cannot run here: it ends on
     * ReflectionProperty::isWritable(), which is PHP 8.6 only, while the polyfill
     * is disabled from that version on. This covers the rest of it.
     */
    public function testPropertiesAreReadonly()
    {
        $duration = Duration::fromSeconds(1);

        // "modification allowed during cloning" must have no lasting effect
        foreach ([$duration, clone $duration] as $subject) {
            try {
                $subject->seconds = 2;
                $this->fail('Writing a readonly property should throw.');
            } catch (\Error $e) {
                $this->assertSame('Cannot modify readonly property Time\Duration::$seconds', $e->getMessage());
            }

            $property = new \ReflectionProperty($subject, 'seconds');

            foreach (['setValue', 'setRawValue', 'setRawValueWithoutLazyInitialization'] as $method) {
                if (!method_exists($property, $method)) {
                    continue;
                }

                try {
                    $property->$method($subject, 2);
                    $this->fail(\sprintf('ReflectionProperty::%s() should throw on a readonly property.', $method));
                } catch (\Error $e) {
                    $this->assertSame('Cannot modify readonly property Time\Duration::$seconds', $e->getMessage());
                }
            }

            $this->assertSame(1, $subject->seconds);
        }
    }
}
