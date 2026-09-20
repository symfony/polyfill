<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Uuid;

/**
 * Lets UuidTest stop the clock, so that generating within one microsecond can be tested.
 */
function microtime($asFloat = null)
{
    return $GLOBALS['__uuid_frozen_microtime'] ?? \microtime($asFloat);
}
