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
class PartsIterator implements \Iterator
{
    public const KEY_SEQUENTIAL = 0;
    public const KEY_LEFT = 1;
    public const KEY_RIGHT = 2;

    private $iterator;
    private $text = '';
    private $type;
    private $key = 0;
    private $start = 0;
    private $end = BreakIterator::DONE;

    /**
     * @internal
     */
    public function __construct(BreakIterator $iterator, int $type)
    {
        $this->iterator = $iterator;
        $this->type = $type;
    }

    public function getBreakIterator()
    {
        return $this->iterator;
    }

    public function getRuleStatus()
    {
        return $this->iterator instanceof RuleBasedBreakIterator || $this->iterator instanceof \IntlRuleBasedBreakIterator ? $this->iterator->getRuleStatus() : 0;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return BreakIterator::DONE === $this->end ? null : substr($this->text, $this->start, $this->end - $this->start);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        switch ($this->type) {
            case self::KEY_LEFT: return $this->start;
            case self::KEY_RIGHT: return $this->end;
            default: return $this->key;
        }
    }

    public function next(): void
    {
        $this->start = $this->end;
        $this->end = $this->iterator->next();
        ++$this->key;
    }

    public function rewind(): void
    {
        $this->key = 0;
        $this->text = (string) $this->iterator->getText();
        $this->start = $this->iterator->first();
        $this->end = $this->iterator->next();
    }

    public function valid(): bool
    {
        return BreakIterator::DONE !== $this->end;
    }
}
