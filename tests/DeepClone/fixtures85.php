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

#[\Attribute(\Attribute::TARGET_ALL | \Attribute::IS_REPEATABLE)]
class ConstExprAttr
{
    public array $args;

    public function __construct(mixed ...$args)
    {
        $this->args = $args;
    }
}

#[ConstExprAttr(static function (): string { return self::SECRET; })]
class ConstExprClosureFixture
{
    private const SECRET = 'class-secret';

    public const CALLBACKS = ['first' => static function (): string { return 'const-value'; }];

    #[ConstExprAttr(
        static function (): string { return 'multi-0'; },
        static function (): string { return 'multi-1'; },
    )]
    public const TAGGED = 1;

    #[ConstExprAttr(cb: [1, ['x' => static function (int $i): int { return $i * 2; }]])]
    public string $tagged = 'v';

    public ?\Closure $factory = static function (): string { return 'prop-default'; };

    public static ?\Closure $staticFactory = static function (): string { return 'static-prop-default'; };

    #[ConstExprAttr('not-a-closure')]
    #[ConstExprAttr(static function (): string { return 'repeated'; })]
    public function tagged(
        #[ConstExprAttr(static function (): string { return 'param-attr'; })]
        ?\Closure $cb = static function (): string { return 'param-default'; },
    ): void {
    }
}

#[ConstExprAttr(static function (): string { return 'first'; }, static function (): string { return 'second'; })]
class ConstExprAmbiguousFixture
{
}

#[ConstExprAttr(static function (): string { return 'noargs'; }, static function (int $x): string { return 'witharg'; })]
class ConstExprSameLineFixture
{
}

enum ConstExprEnumFixture: string
{
    #[ConstExprAttr(static function (): string { return 'enum-case-attr'; })]
    case Active = 'A';

    public const FILTER = static function (): string { return 'enum-const'; };
}

trait ConstExprTraitFixture
{
    #[ConstExprAttr(static function (): string { return 'trait-attr'; })]
    public function traitTagged(): void
    {
    }
}

class ConstExprTraitUserFixture
{
    use ConstExprTraitFixture;
}

class ConstExprPromotedFixture
{
    public function __construct(
        #[ConstExprAttr(static function (): string { return 'promoted-attr'; })]
        public int $promoted = 1,
    ) {
    }
}

class ConstraintLikeFixture
{
    public function __construct(
        public mixed $callback = null,
        public array $constraints = [],
    ) {
    }
}

class ConstExprFactoryFixture
{
    public const FACTORY = static function (): \Closure { return static function (): string { return 'inner'; }; };
}

class ConstExprRuntimeCollisionFixture
{
    #[ConstExprAttr(static function (): string { return 'const-expr'; })] public static function make(): \Closure { return static function (): string { return 'runtime'; }; }
}

#[ConstExprAttr(new ConstExprDualSurfaceFixture())]
class ConstExprDualSurfaceFixture
{
    public function __construct(
        public ?\Closure $cb = static function (): string { return 'dual-surface'; },
    ) {
    }
}

class ConstExprTraitAliasFixture
{
    use ConstExprTraitFixture {
        traitTagged as traitAliased;
    }
}

class ConstExprFccFixture
{
    #[ConstExprAttr(self::helper(...))]
    public static function helper(): bool
    {
        return true;
    }
}

class ConstExprHookedFixture
{
    public string $virtual {
        #[ConstExprAttr(static function (): string { return 'get-hook-attr'; })]
        get => 'vx';
    }

    public string $stored = 'init' {
        set (#[ConstExprAttr(static function (): string { return 'set-hook-param-attr'; })] string $value) {
            $this->stored = $value;
        }
    }
}

class ConstExprGlobalFccFixture
{
    #[ConstExprAttr(strlen(...))]
    public string $p = '';
}

class ConstExprCrossTarget
{
    public static function check(): bool { return true; }
}

function dc_constexpr_global_fcc(): int { return 7; }

class ConstExprCrossFccFixture
{
    #[ConstExprAttr(ConstExprCrossTarget::check(...))] public int $x = 0;
    #[ConstExprAttr(strlen(...))] public int $g = 0;
    #[ConstExprAttr(dc_constexpr_global_fcc(...))] public int $u = 0;
}
