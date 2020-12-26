<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Intl\Icu\Verification;

use Symfony\Polyfill\Tests\Intl\Icu\AbstractNumberFormatterTest;

/**
 * Note that there are some values written like -2147483647 - 1. This is the lower 32bit int max and is a known
 * behavior of PHP.
 *
 * @requires extension intl
 *
 * @group class-polyfill
 */
class NumberFormatterTest extends AbstractNumberFormatterTest
{
    protected function setUp(): void
    {
        \Locale::setDefault('en');

        if (version_compare(\INTL_ICU_VERSION, '55.1', '<')) {
            $this->markTestSkipped('ICU version 55.1 is required.');
        }
    }

    public function testCreate()
    {
        $this->assertInstanceOf('\NumberFormatter', \NumberFormatter::create('en', \NumberFormatter::DECIMAL));
    }

    public function testGetTextAttribute()
    {
        if (version_compare(\INTL_ICU_VERSION, '57.1', '<')) {
            $this->markTestSkipped('ICU version 57.1 is required.');
        }

        parent::testGetTextAttribute();
    }

    protected function getNumberFormatter(?string $locale = 'en', string $style = null, string $pattern = null): \NumberFormatter
    {
        return new \NumberFormatter($locale, $style, $pattern);
    }

    protected function getIntlErrorMessage(): string
    {
        return intl_get_error_message();
    }

    protected function getIntlErrorCode(): int
    {
        return intl_get_error_code();
    }

    protected function isIntlFailure($errorCode): bool
    {
        return intl_is_failure($errorCode);
    }
}
