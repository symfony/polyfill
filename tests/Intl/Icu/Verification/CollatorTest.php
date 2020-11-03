<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Intl\Icu\Tests\Verification;

use Symfony\Polyfill\Intl\Icu\Tests\AbstractCollatorTest;

/**
 * Verifies that {@link AbstractCollatorTest} matches the behavior of the
 * {@link \Collator} class in a specific version of ICU.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @requires extension intl
 */
class CollatorTest extends AbstractCollatorTest
{
    protected function setUp(): void
    {
        \Locale::setDefault('en');
    }

    protected function getCollator(?string $locale): \Collator
    {
        return new \Collator($locale);
    }
}
