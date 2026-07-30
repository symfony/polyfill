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

/**
 * Runs the phpt tests of the native implementation, borrowed verbatim from
 * php-src (ext/date/tests/time/duration/), against the polyfill.
 *
 * Two of them are not taken: new.phpt, because a userland class cannot reject
 * ReflectionClass::newInstanceWithoutConstructor(), and readonly.phpt, because
 * it ends on ReflectionProperty::isWritable(), added in PHP 8.6, while the
 * polyfill is disabled from that version on. DurationTest covers the rest of
 * their assertions.
 *
 * @requires PHP >= 8.1
 */
class PhptTest extends TestCase
{
    protected function setUp(): void
    {
        if (\PHP_VERSION_ID >= 80600) {
            $this->markTestSkipped('The Time\Duration polyfill is only used on PHP < 8.6.');
        }
    }

    /**
     * @dataProvider providePhptFiles
     */
    public function testPhpt(string $file)
    {
        $sections = self::parse($file);

        if (isset($sections['SKIPIF'])) {
            $output = ltrim($this->execute($file, $sections['SKIPIF']));
            if (0 === strpos($output, 'skip')) {
                $this->markTestSkipped(trim(substr($output, 4)));
            }
        }

        $output = rtrim(str_replace("\r\n", "\n", $this->execute($file, $sections['FILE'])));

        if (isset($sections['EXPECTF'])) {
            $expected = rtrim(str_replace("\r\n", "\n", $sections['EXPECTF']));
            if (!preg_match(self::expectfToRegex($expected), $output)) {
                $this->assertSame($expected, $output);
            }
            $this->addToAssertionCount(1);
        } else {
            $expected = rtrim(str_replace("\r\n", "\n", $sections['EXPECT']));
            // object handles differ between native and polyfill runs
            $normalize = static function (string $s) {
                return preg_replace('/(object\([^)]++\))#\d+/', '$1', $s);
            };
            $this->assertSame($normalize($expected), $normalize($output));
        }
    }

    public static function providePhptFiles(): array
    {
        $baseDir = __DIR__.'/phpt';
        $files = [];

        foreach (glob($baseDir.'/{,*/}*.phpt', \GLOB_BRACE) as $file) {
            $files[substr($file, 1 + strlen($baseDir), -5)] = [$file];
        }

        ksort($files);

        return $files;
    }

    private static function parse(string $file): array
    {
        $sections = [];
        $current = null;
        foreach (file($file) as $line) {
            if (preg_match('/^--([A-Z_]+)--\s*$/', $line, $m)) {
                $current = $m[1];
                $sections[$current] = '';
            } elseif (null !== $current) {
                $sections[$current] .= $line;
            }
        }

        return $sections;
    }

    /**
     * The code runs from a temporary file next to the phpt so that __DIR__
     * resolves helper.inc; the autoloader is injected via auto_prepend_file
     * since the phpt files are unmodified copies.
     */
    private function execute(string $phptFile, string $code): string
    {
        $phpFile = substr($phptFile, 0, -1);
        file_put_contents($phpFile, $code);

        $proc = proc_open([
            \PHP_BINARY,
            '-n',
            '-d', 'error_reporting=-1',
            '-d', 'display_errors=1',
            '-d', 'auto_prepend_file='.\dirname(__DIR__, 2).'/vendor/autoload.php',
            $phpFile,
        ], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        try {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($proc);
        } finally {
            unlink($phpFile);
        }

        return '' !== $stdout ? $stdout : $stderr;
    }

    private static function expectfToRegex(string $expected): string
    {
        return '~^'.strtr(preg_quote($expected, '~'), [
            '%%' => '%',
            '%e' => preg_quote(\DIRECTORY_SEPARATOR, '~'),
            '%s' => '[^\r\n]+',
            '%S' => '[^\r\n]*',
            '%a' => '.+',
            '%A' => '.*',
            '%w' => '\s*',
            '%i' => '[+-]?\d+',
            '%d' => '\d+',
            '%x' => '[0-9a-fA-F]+',
            '%f' => '[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[Ee][+-]?\d+)?',
            '%c' => '.',
        ]).'$~s';
    }
}
