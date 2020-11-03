<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Intl\Icu\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Intl\Icu\Currencies;

class CurrenciesTest extends TestCase
{
    public function testMetadata()
    {
        $en = json_decode(file_get_contents(\dirname(__DIR__, 3).'/vendor/symfony/intl/Resources/data/currencies/en.json'), true);
        $meta = json_decode(file_get_contents(\dirname(__DIR__, 3).'/vendor/symfony/intl/Resources/data/currencies/meta.json'), true);
        $data = [];

        foreach ($en['Names'] as $code => [$symbol, $name]) {
            $data[$code] = [$symbol];
        }

        foreach ($meta['Meta'] as $code => [$fractionDigit, $roundingIncrement]) {
            $data[$code] = ($data[$code] ?? []) + [1 => $fractionDigit, $roundingIncrement];
        }

        $data = "<?php\n\nreturn ".var_export($data, true).";\n";

        $this->assertStringEqualsFile(\dirname(__DIR__, 3).'/src/Intl/Icu/Resources/currencies.php', $data);
    }
}
