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

use Symfony\Polyfill\Tests\Intl\Icu\AbstractLocaleTest;

/**
 * Verifies that {@link AbstractLocaleTest} matches the behavior of the
 * {@link Locale} class with a specific version of ICU.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @requires extension intl
 *
 * @group class-polyfill
 */
class LocaleTest extends AbstractLocaleTest
{
    protected function setUp(): void
    {
        \Locale::setDefault('en');
    }

    protected function call($methodName)
    {
        $args = \array_slice(\func_get_args(), 1);

        return \Locale::{$methodName}(...$args);
    }
}
