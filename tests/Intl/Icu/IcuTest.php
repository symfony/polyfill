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

use Symfony\Polyfill\Intl\Icu\Icu;

class IcuTest extends AbstractIcuTest
{
    protected function getIntlErrorName($errorCode)
    {
        return Icu::getErrorName($errorCode);
    }
}
