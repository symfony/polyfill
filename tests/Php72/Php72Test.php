<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php72;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Php72\Php72 as p;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @covers \Symfony\Polyfill\Php72\Php72::<!public>
 */
class Php72Test extends TestCase
{
    /**
     * @covers \Symfony\Polyfill\Php72\Php72::utf8_encode
     * @covers \Symfony\Polyfill\Php72\Php72::utf8_decode
     */
    public function testUtf8Encode()
    {
        $s = array_map('chr', range(0, 255));
        $s = implode('', $s);
        $e = utf8_encode($s);

        $this->assertSame(utf8_encode($s), utf8_encode($s));
        $this->assertSame(utf8_decode($e), utf8_decode($e));
        $this->assertSame('??', utf8_decode('Σ어'));

        $s = 444;

        $this->assertSame(utf8_encode($s), utf8_encode($s));
        $this->assertSame(utf8_decode($s), utf8_decode($s));
    }

    /**
     * @covers \Symfony\Polyfill\Php72\Php72::php_os_family
     */
    public function testPhpOsFamily()
    {
        $this->assertTrue(\defined('PHP_OS_FAMILY'));
        $this->assertSame(\PHP_OS_FAMILY, p::php_os_family());
    }

    public function testPhpFloat()
    {
        $this->assertSame(15, \PHP_FLOAT_DIG);
        $this->assertSame(2.2204460492503E-16, \PHP_FLOAT_EPSILON);
        $this->assertSame(2.2250738585072E-308, \PHP_FLOAT_MIN);
        $this->assertSame(1.7976931348623157E+308, \PHP_FLOAT_MAX);
    }

    /**
     * @covers \Symfony\Polyfill\Php72\Php72::spl_object_id
     */
    public function testSplObjectId()
    {
        $obj = new \stdClass();
        $id = spl_object_id($obj);
        ob_start();
        var_dump($obj);
        $dump = ob_get_clean();

        $this->assertStringContainsString("#$id ", $dump);
    }

    /**
     * @covers \Symfony\Polyfill\Php72\Php72::spl_object_id
     * @requires PHP < 8
     */
    public function testSplObjectIdWithInvalidType()
    {
        $this->assertNull(@spl_object_id(123));
    }

    /**
     * @covers \Symfony\Polyfill\Php72\Php72::spl_object_id
     */
    public function testSplObjectIdUniqueValues()
    {
        // Should be able to represent more than 2**16 ids on 32-bit systems.
        $result = [];
        for ($i = 0; $i < 70000; ++$i) {
            $obj = new \stdClass();
            $result[spl_object_id($obj)] = $obj;
        }
        $this->assertCount(70000, $result);
    }

    /**
     * @covers \Symfony\Polyfill\Php72\Php72::sapi_windows_vt100_support
     */
    public function testSapiWindowsVt100Support()
    {
        if ('\\' !== \DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('Windows only test');
        }

        $this->assertFalse(sapi_windows_vt100_support(\STDIN, true));
    }

    /**
     * @covers \Symfony\Polyfill\Php72\Php72::sapi_windows_vt100_support
     */
    public function testSapiWindowsVt100SupportWarnsOnInvalidInputType()
    {
        if ('\\' !== \DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('Windows only test');
        }

        try {
            sapi_windows_vt100_support('foo', true);
        } catch (\PHPUnit\Framework\Error\Warning $e) {
            $this->expectWarning();
            $this->expectWarningMessage('expects parameter 1 to be resource');

            throw $e;
        }
    }

    /**
     * @covers \Symfony\Polyfill\Php72\Php72::sapi_windows_vt100_support
     */
    public function testSapiWindowsVt100SupportWarnsOnInvalidStream()
    {
        if ('\\' !== \DIRECTORY_SEPARATOR) {
            $this->markTestSkipped('Windows only test');
        }

        try {
            sapi_windows_vt100_support(fopen('php://memory', 'wb'), true);
        } catch (\PHPUnit\Framework\Error\Warning $e) {
            $this->expectWarning();
            $this->expectWarningMessage('was not able to analyze the specified stream');

            throw $e;
        }
    }

    /**
     * @covers \Symfony\Polyfill\Php72\Php72::stream_isatty
     */
    public function testStreamIsatty()
    {
        $fp = fopen('php://temp', 'r+b');
        $this->assertFalse(stream_isatty($fp));
        fclose($fp);
    }

    /**
     * @covers \Symfony\Polyfill\Php72\Php72::stream_isatty
     * @requires PHP < 8
     */
    public function testStreamIsattyWarnsOnInvalidInputType()
    {
        try {
            stream_isatty('foo');
        } catch (\PHPUnit\Framework\Error\Warning $e) {
            $this->expectWarning();
            $this->expectWarningMessage('expects parameter 1 to be resource');

            throw $e;
        }
    }

    /**
     * @covers \Symfony\Polyfill\Php72\Php72::mb_chr
     */
    public function testChr()
    {
        $this->assertSame("\xF0\xA0\xAE\xB7", mb_chr(0x20BB7));
        $this->assertSame("\xE9", mb_chr(0xE9, 'CP1252'));
    }

    /**
     * @covers \Symfony\Polyfill\Php72\Php72::mb_ord
     */
    public function testOrd()
    {
        $this->assertSame(0x20BB7, mb_ord("\xF0\xA0\xAE\xB7"));
        $this->assertSame(0xE9, mb_ord("\xE9", 'CP1252'));
    }

    public function testScrub()
    {
        $subst = mb_substitute_character();
        mb_substitute_character('none');
        $this->assertSame('ab', mb_scrub("a\xE9b"));
        mb_substitute_character($subst);
    }
}
