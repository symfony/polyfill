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

/**
 * @group class-polyfill
 */
class CurrenciesTest extends TestCase
{
    /**
     * @requires PHP 7.2
     */
    public function testMetadata()
    {
        $dataDir = \dirname(__DIR__, 3).'/vendor/symfony/intl/Resources/data/currencies/';

        if (is_file($dataDir.'en.php')) {
            $en = require $dataDir.'en.php';
            $meta = require $dataDir.'meta.php';
        } else {
            $en = json_decode(file_get_contents($dataDir.'en.json'), true);
            $meta = json_decode(file_get_contents($dataDir.'meta.json'), true);
        }
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
