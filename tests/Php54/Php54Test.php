<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php54;

use Symfony\Polyfill\Php54\Php54 as p;

class Php54Test extends \PHPUnit_Framework_TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testHttpResponseCodeDefaultValueIs200() {
        $code = p::http_response_code(null);

        $this->assertSame(200, $code);
    }

    /**
     * @runInSeparateProcess
     */
    public function testHttpResponseCodePreviousValueReturned() {
        $code1 = p::http_response_code(403);
        $code2 = p::http_response_code(null);

        $this->assertSame(200, $code1);
        $this->assertSame(403, $code2);
    }

    /**
     * @runInSeparateProcess
     */
    public function testHttpResponseCodeNumericStringsAreConvertedToIntegers() {
        $code1 = p::http_response_code('403');
        $code2 = p::http_response_code(null);

        $this->assertSame(200, $code1);
        $this->assertSame(403, $code2);
    }

    /**
     * @runInSeparateProcess
     * @expectedException        PHPUnit_Framework_Error_Warning
     * @expectedExceptionCode    E_USER_WARNING
     * @expectedExceptionMessage http_response_code() expects parameter 1 to be long, string given
     */
    public function testHttpResponseCodeNonNumericStringsAreInvalid() {
        p::http_response_code('foo-bar');
    }
}
