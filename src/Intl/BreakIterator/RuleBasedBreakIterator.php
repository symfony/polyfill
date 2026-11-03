<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Intl\BreakIterator;

/**
 * @internal
 */
class RuleBasedBreakIterator extends BreakIterator
{
    public function __construct(string $rules, bool $compiled = false)
    {
        throw new \LogicException('Custom break rules are not supported by the polyfill, install the "intl" extension.');
    }

    public function getRuleStatus()
    {
        return $this->getRuleStatusInternal();
    }

    public function getRuleStatusVec()
    {
        return [$this->getRuleStatusInternal()];
    }
}
