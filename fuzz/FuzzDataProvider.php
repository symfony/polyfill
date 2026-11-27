<?php

final class FuzzDataProvider
{
    private $data;
    private $offset = 0;
    private $end;

    public function __construct(string $data)
    {
        $this->data = $data;
        $this->end = \strlen($data);
    }

    public function consumeBool(): bool
    {
        return 0 !== $this->consumeUint8() % 2;
    }

    public function consumeUint8(): int
    {
        return $this->offset === $this->end ? 0 : \ord($this->data[--$this->end]);
    }

    public function consumeInt(int $min, int $max): int
    {
        if ($min > $max) {
            throw new \ValueError('The minimum must not exceed the maximum.');
        }

        return $min === $max ? $min : $min + $this->consumeUint8() % ($max - $min + 1);
    }

    public function consumeChoice(array $choices)
    {
        if (!$choices) {
            throw new \ValueError('Cannot choose from an empty array.');
        }

        return $choices[$this->consumeInt(0, \count($choices) - 1)];
    }

    public function consumeBytes(int $maxLength): string
    {
        if (0 > $maxLength) {
            throw new \ValueError('The maximum length must not be negative.');
        }

        $length = $this->consumeInt(0, $maxLength);
        $length = \min($length, $this->end - $this->offset);
        $bytes = substr($this->data, $this->offset, $length);
        $this->offset += $length;

        return $bytes;
    }

    public function consumeRemainingBytes(): string
    {
        $bytes = substr($this->data, $this->offset, $this->end - $this->offset);
        $this->offset = $this->end;

        return $bytes;
    }
}
