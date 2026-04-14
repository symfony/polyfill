<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Tests\DeepClone;

enum DeepCloneColor
{
    case Red;
    case Blue;
}

enum DeepCloneSuit: string
{
    case Hearts = 'H';
    case Spades = 'S';
}

class ClosureFixture
{
    public function instanceMethod(): string
    {
        return 'instance';
    }

    public static function staticMethod(): string
    {
        return 'static';
    }

    private function privateMethod(): string
    {
        return 'private';
    }

    public function getPrivateClosure(): \Closure
    {
        return \Closure::fromCallable([$this, 'privateMethod']);
    }
}

class DeepCloneFinalError extends \Error
{
}

class DeepCloneSerializeFixture
{
    public function __construct(public string $name = '', public int $val = 0)
    {
    }

    public function __serialize(): array
    {
        return ['n' => $this->name, 'v' => $this->val];
    }

    public function __unserialize(array $data): void
    {
        $this->name = $data['n'];
        $this->val = $data['v'];
    }
}

class DeepCloneWakeupFixture
{
    public string $status = 'sleeping';

    public function __wakeup(): void
    {
        $this->status = 'awake';
    }
}

class DeepCloneSleepFixture
{
    public string $keep = '';
    public string $skip = '';

    public function __sleep(): array
    {
        return ['keep'];
    }
}

class DeepCloneParentNoUnser
{
    private string $foo = 'foo';
}

class DeepCloneChildNoUnser extends DeepCloneParentNoUnser
{
    public string $baz = '';
    private string $bar = '';

    public function __serialize(): array
    {
        return ['foo' => 'foo', 'baz' => 'ccc', 'bar' => 'ddd'];
    }
}

class DeepCloneUnserOnly
{
    public string $foo = '';

    public function __unserialize(array $data): void
    {
        $this->foo = $data['foo'] ?? '';
    }
}

class DeepCloneSleepPrivate
{
    public string $good = '';
    protected string $foo = '';
    private string $bar = '';

    public function __sleep(): array
    {
        return ['good', 'foo', "\0*\0foo", "\0".self::class."\0bar"];
    }

    public function setAll(string $g, string $f, string $b): void
    {
        $this->good = $g;
        $this->foo = $f;
        $this->bar = $b;
    }
}

class DeepCloneParentSleep
{
    private string $secret = '';
}

class DeepCloneChildSleep extends DeepCloneParentSleep
{
    public string $pub = '';

    // __sleep returns unmangled "secret" — should NOT match parent's private.
    public function __sleep(): array
    {
        return ['pub', 'secret'];
    }
}

class DeepCloneParentClass
{
    public string $pub = 'pub_default';
    protected int $prot = 0;
    private string $priv = 'parent_priv';

    public function setProt(int $v): void
    {
        $this->prot = $v;
    }

    public function setPriv(string $v): void
    {
        $this->priv = $v;
    }
}

class DeepCloneChildClass extends DeepCloneParentClass
{
    private string $childPriv = 'child_default';

    public function setChildPriv(string $v): void
    {
        $this->childPriv = $v;
    }
}

class DeepCloneReadonlyFixture
{
    public function __construct(
        public readonly string $name,
        public readonly int $value,
    ) {
    }
}

class HydrateFoo
{
    protected $prot;
    private $priv;
    public readonly int $ro;
}

#[\AllowDynamicProperties]
class HydrateBar extends HydrateFoo
{
    private $priv;
}

class CacheIsolationParent
{
    private string $priv = 'def';
    public function getPriv(): string { return $this->priv; }
    public function setPriv(string $v): void { $this->priv = $v; }
}

class CacheIsolationChild extends CacheIsolationParent
{
    public string $pub = '';
}

abstract class AbstractScopeBase
{
    public string $sourceEntity;
    public string $mappedBy;
    private string $secret = 'def';

    public function __sleep(): array
    {
        return ['sourceEntity', 'mappedBy'];
    }

    public function setSecret(string $v): void
    {
        $this->secret = $v;
    }
}

class AbstractScopeChild extends AbstractScopeBase
{
    public function __construct(string $src)
    {
        $this->sourceEntity = $src;
    }
}

abstract class AbstractWithPrivate
{
    private string $secret = 'default';

    public function set(string $v): void
    {
        $this->secret = $v;
    }

    public function get(): string
    {
        return $this->secret;
    }
}

class AbstractWithPrivateChild extends AbstractWithPrivate
{
    public string $pub = '';
}

class HydrateBase
{
    private string $secret = '';
    public function getSecret(): string { return $this->secret; }
}

class HydrateChild extends HydrateBase
{
    protected int $num = 0;
    public string $pub = '';
    public function getNum(): int { return $this->num; }
}

class HydrateReadonly
{
    public string $status = 'new';
    private readonly int $value;
    public function __construct(int $value) { $this->value = $value; }
    public function getValue(): int { return $this->value; }
}

class HydrateGP
{
    private string $secret = '';
    public function getSecret(): string { return $this->secret; }
}

class HydrateP extends HydrateGP
{
    private int $mid = 0;
    public function getMid(): int { return $this->mid; }
}

class HydrateC extends HydrateP
{
    public string $pub = '';
}

enum HydrateColor
{
    case Red;
}

trait HydrateTrait
{
}
