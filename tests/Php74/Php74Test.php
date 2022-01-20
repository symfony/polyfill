<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\Php74;

use ArrayObject;
use PHPUnit\Framework\TestCase;

/**
 * @author Ion Bazan <ion.bazan@gmail.com>
 */
class Php74Test extends TestCase
{
    /**
     * @covers \Symfony\Polyfill\Php74\Php74::get_mangled_object_vars
     */
    public function testGetMangledObjectVarsOnObject()
    {
        $obj = new B();
        $obj->dyn = 5;
        $obj->{'6'} = 6;

        $this->assertEqualsCanonicalizing([
            "\0".'Symfony\Polyfill\Tests\Php74\B'."\0".'priv' => 4,
            'pub' => 1,
            "\0".'*'."\0".'prot' => 2,
            "\0".'Symfony\Polyfill\Tests\Php74\A'."\0".'priv' => 3,
            'dyn' => 5,
            6 => 6,
        ], get_mangled_object_vars($obj));
    }

    /**
     * @covers \Symfony\Polyfill\Php74\Php74::get_mangled_object_vars
     */
    public function testGetMangledObjectVarsOnArrayObject()
    {
        $ao = new AO(['x' => 'y']);
        $ao->dyn = 2;

        $this->assertSame([
            "\0".'Symfony\Polyfill\Tests\Php74\AO'."\0".'priv' => 1,
            'dyn' => 2,
        ], get_mangled_object_vars($ao));
    }

    /**
     * @covers \Symfony\Polyfill\Php74\Php74::get_mangled_object_vars
     * @requires PHP < 8
     */
    public function testGetMangledObjectVarsOnNonObject()
    {
        $this->assertNull(@get_mangled_object_vars(0));
        $this->assertNull(@get_mangled_object_vars(true));
        $this->assertNull(@get_mangled_object_vars('string'));
        $this->expectWarning();
        $this->expectWarningMessage('expects parameter 1 to be object');
        get_mangled_object_vars(1);
    }

    /**
     * @covers \Symfony\Polyfill\Php74\Php74::password_algos
     */
    public function testPasswordAlgos()
    {
        $algos = password_algos();

        if (\defined('PASSWORD_BCRYPT')) {
            $this->assertContains(\PASSWORD_BCRYPT, $algos);
        }

        if (\defined('PASSWORD_ARGON2I')) {
            $this->assertContains(\PASSWORD_ARGON2I, $algos);
        }

        if (\defined('PASSWORD_ARGON2ID')) {
            $this->assertContains(\PASSWORD_ARGON2ID, $algos);
        }
    }

    /**
     * @covers \Symfony\Polyfill\Php74\Php74::mb_str_split
     */
    public function testStrSplit()
    {
        $this->assertSame(['한', '국', '어'], mb_str_split('한국어'));
        $this->assertSame(['по', 'бе', 'да'], mb_str_split('победа', 2));
        $this->assertSame(['źre', 'bię'], mb_str_split('źrebię', 3));
        $this->assertSame(['źr', 'ebi', 'ę'], mb_str_split('źrebię', 3, 'ASCII'));
        $this->assertSame(['alpha', 'bet'], mb_str_split('alphabet', 5));
        $this->assertSame(['e', '́', '💩', '𐍈'], mb_str_split('é💩𐍈', 1, 'UTF-8'));
    }

    /**
     * @covers \Symfony\Polyfill\Php74\Php74::mb_str_split
     * @requires PHP < 8
     */
    public function testStrSplitWithInvalidValues()
    {
        $this->assertSame([], mb_str_split('', 1, 'UTF-8'));
        $this->assertFalse(@mb_str_split('победа', 0));
        $this->assertNull(@mb_str_split([], 0));

        $this->expectWarning();
        $this->expectWarningMessage('The length of each segment must be greater than zero');
        mb_str_split('победа', 0);
    }
}

class A
{
    public $pub = 1;
    protected $prot = 2;
    private $priv = 3;
}

class B extends A
{
    private $priv = 4;
}

class AO extends ArrayObject
{
    private $priv = 1;

    #[\ReturnTypeWillChange]
    public function getFlags()
    {
        return self::ARRAY_AS_PROPS | self::STD_PROP_LIST;
    }
}
