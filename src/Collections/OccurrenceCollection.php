<?php

namespace Ninja\Censor\Collections;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Ninja\Censor\ValueObject\Position;

/**
 * @extends Collection<int, Position>
 */
final class OccurrenceCollection extends Collection
{
    /**
     * @param  array<Position>  $positions
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $positions = [])
    {
        $this->validatePositions($positions);
        parent::__construct($positions);
    }

    public function density(int $totalLength): float
    {
        if ($totalLength === 0) {
            return 0.0;
        }

        return $this->sum(fn (Position $pos) => $pos->length()) / $totalLength;
    }

    /**
     * @return array<Position>
     */
    public function toArray(): array
    {
        return $this->map(fn (Position $pos) => [
            'start' => $pos->start(),
            'end' => $pos->end(),
            'length' => $pos->length(),
        ])->all();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function addPosition(Position $position): self
    {
        $this->validatePosition($position);
        $this->push($position);

        return $this;
    }

    public function ordered(): self
    {
        return $this->sortBy(fn (Position $pos) => $pos->start());
    }

    /**
     * @param  array<Position>  $positions
     *
     * @throws InvalidArgumentException
     */
    private function validatePositions(array $positions): void
    {
        foreach ($positions as $position) {
            $this->validatePosition($position);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validatePosition(Position $newPosition): void
    {
        $hasOverlap = $this->contains(
            fn (Position $existing) => $existing->overlaps($newPosition)
        );

        if ($hasOverlap) {
            throw new InvalidArgumentException('New position overlaps with existing positions');
        }
    }

    public function apply(string $text, string $replacement = '*'): string
    {
        $result = $text;
        $offset = 0;

        foreach ($this->ordered() as $position) {
            $replacementText = str_repeat($replacement, $position->length());
            $result = substr_replace(
                $result,
                $replacementText,
                $position->start() + $offset,
                $position->length()
            );
        }

        return $result;
    }

    /**
     * @param  array<array{start: int, length: int}>  $ranges
     */
    public static function fromRanges(array $ranges): self
    {
        $positions = array_map(
            fn (array $range) => new Position($range['start'], $range['length']),
            $ranges
        );

        return new self($positions);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function merge($items): self
    {
        if ($items instanceof OccurrenceCollection) {
            foreach ($items as $position) {
                $this->validatePosition($position);
            }
        }

        return parent::merge($items);
    }
}
