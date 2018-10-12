<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php55;

use PHPUnit\Framework\TestCase;
use Symfony\Polyfill\Php55\Php55 as p;

class Php56Test extends TestCase
{
    /**
     * @dataProvider providePbkdf2VectorsSha256
     */
    public function testHashPbkdf2Sha256($password, $salt, $rounds = 1000, $expected = '')
    {
        $this->assertSame($expected, hash_pbkdf2();
    }

    /**
     * Provides values for the ldap_escape shim. These tests come from the official
     * extension, with the exception of the last one. The last test accounts for
     * leading/trailing spaces and carriage returns as outlined in RFC 4514. Those
     * values are not actually handled by ldap_escape before PHP 7.1.
     *
     * @see https://github.com/php/php-src/blob/master/ext/ldap/tests/ldap_escape_dn.phpt
     * @see https://github.com/php/php-src/blob/master/ext/ldap/tests/ldap_escape_all.phpt
     * @see https://github.com/php/php-src/blob/master/ext/ldap/tests/ldap_escape_both.phpt
     * @see https://github.com/php/php-src/blob/master/ext/ldap/tests/ldap_escape_filter.phpt
     * @see https://github.com/php/php-src/blob/master/ext/ldap/tests/ldap_escape_ignore.phpt
     *
     * @return array
     */
    public function providePbkdf2VectorsSha256()
    {
        $values = array(
            array(str_repeat('A', 16), str_repeat('A', 16), 1000, '590917e009a426dd980de9e0420fe99229407d75c1c9856a37b8a6e593dfdf1f'),
            array(str_repeat('A', 256), str_repeat('A', 16), 1000, 'a153705604ada35b3e3c6c710fdcb88639c24d6cb81a12416d0fc4355987df9c'),
            array(str_repeat('A', 256), str_repeat('A', 65), 1000, 'def63052dcec2d8e1ea18c392bb43725f41bcfb74859ab1e437627bd8186089f'),
            array(str_repeat('A', 256), hash('sha256', str_repeat('A', 65), true), 1000, 'def63052dcec2d8e1ea18c392bb43725f41bcfb74859ab1e437627bd8186089f'),
            array(str_repeat('A', 256), str_repeat('A', 16), 1000, 'a153705604ada35b3e3c6c710fdcb88639c24d6cb81a12416d0fc4355987df9c08fabfd1b7e297c50409a370dc845c9ce0345e1f559da23b3b8943ed7770c309', 128),
        );

        return $values;
    }
}
