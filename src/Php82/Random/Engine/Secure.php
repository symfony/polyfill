<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Php82\Random\Engine;

/**
 * @author Tim Düsterhus <tim@bastelstu.be>
 *
 * @internal
 */
class Secure
{
    public function __construct()
    {
    }

    public function generate(): string
    {
        return \random_bytes(\PHP_INT_SIZE);
    }

    public function __sleep(): array
    {
        throw new \Exception("Serialization of 'Random\\Engine\\Secure' is not allowed");
    }

    public function __wakeup(): void
    {
        throw new \Exception("Unserialization of 'Random\\Engine\\Secure' is not allowed");
    }
}
