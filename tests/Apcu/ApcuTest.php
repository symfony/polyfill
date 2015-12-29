<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Apcu;

/**
 * @requires extension apc
 */
class ApcuTest extends \PHPUnit_Framework_TestCase
{
    public function testApcu()
    {
        apcu_delete(__CLASS__);

        $this->assertFalse(apcu_exists(__CLASS__));
        $this->assertTrue(apcu_add(__CLASS__, 123));
        $this->assertTrue(apcu_exists(__CLASS__));
        $this->assertSame(array(__CLASS__ => -1), apcu_add(array(__CLASS__ => 123)));
        $this->assertSame(123, apcu_fetch(__CLASS__));
        $this->assertTrue(apcu_store(__CLASS__, 124));
        $this->assertSame(124, apcu_fetch(__CLASS__));
        $this->assertSame(125, apcu_inc(__CLASS__));
        $this->assertSame(124, apcu_dec(__CLASS__));
        $this->assertTrue(apcu_cas(__CLASS__, 124, 123));
        $this->assertFalse(apcu_cas(__CLASS__, 124, 123));
        $this->assertTrue(apcu_delete(__CLASS__));
        $this->assertFalse(apcu_delete(__CLASS__));
        $this->assertArrayHasKey('cache_list', apcu_cache_info());
    }

    public function testAPCUIterator()
    {
        $this->assertTrue(apcu_store(__CLASS__, 456));

        $entries = iterator_to_array(new \APCUIterator('/^'.preg_quote(__CLASS__, '/').'$/'));

        $this->assertSame(array(__CLASS__), array_keys($entries));
        $this->assertSame(__CLASS__, $entries[__CLASS__]['key']);
        $this->assertSame(456, $entries[__CLASS__]['value']);
    }

    public function testApcuEntry()
    {
        apcu_delete(__CLASS__);

        $gen = function ($key) use (&$ok) {
            $ok = null === $ok && __CLASS__ === $key;

            return 789;
        };

        $this->assertSame(789, apcu_entry(__CLASS__, $gen));
        $this->assertTrue($ok);
    }
}
