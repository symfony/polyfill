<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php85;

use PHPUnit\Framework\TestCase;

class PhpFinfoPhpBuildDateTest extends TestCase
{
    public function serverConfigProvider(): array
    {
        return [
            'expose_php=1' => ['-d expose_php=1'],
            'expose_php=0' => ['-d expose_php=0'],
        ];
    }

    /**
     * @dataProvider serverConfigProvider
     */
    public function testFinfoPhpBuildDate(string $iniSetting)
    {
        $spec = [
            1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ];

        $cmd = sprintf(
            '%s %s -S localhost:8086 -t %s',
            \PHP_BINARY,
            $iniSetting,
            escapeshellarg(__DIR__ . '/fixtures')
        );

        $server = @proc_open(('\\' === \DIRECTORY_SEPARATOR ? '' : 'exec ') . $cmd, $spec, $pipes);
        if (!$server) {
            self::markTestSkipped("Unable to start PHP server with $iniSetting");
        }
        sleep(1);

        $ch = curl_init('http://localhost:8086/server-finifo.php');
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z]{3} \d{1,2} \d{4} \d{2}:\d{2}:\d{2}$/',
            trim($response),
            "The build date format is invalid with $iniSetting: $response"
        );

        proc_terminate($server);
        proc_close($server);
    }
}
