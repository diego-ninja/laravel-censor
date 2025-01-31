<?php

namespace Ninja\Censor\ValueObject;

use InvalidArgumentException;

/**
 * @immutable
 */
final readonly class Position
{
    public function __construct(
        private int $start,
        private int $length
    ) {
        if ($start < 0 || $length < 1) {
            throw new InvalidArgumentException('Invalid position values');
        }
    }

    public function start(): int
    {
        return $this->start;
    }

    public function end(): int
    {
        return $this->start + $this->length;
    }

    public function length(): int
    {
        return $this->length;
    }

    public function overlaps(Position $other): bool
    {
        return $this->start < $other->end() && $other->start < $this->end();
    }
}