<?php

namespace Ninja\Censor\Collections;

use Illuminate\Support\Collection;
use Ninja\Censor\ValueObject\Coincidence;
use Ninja\Censor\ValueObject\Confidence;
use Ninja\Censor\ValueObject\Score;

/**
 * @extends Collection<int, Coincidence>
 */
class MatchCollection extends Collection
{
    public function addCoincidence(Coincidence $coincidence): void
    {
        if (! $this->contains(fn (Coincidence $existingItem) => $existingItem->word() === $coincidence->word())) {
            $this->add($coincidence);
        }
    }

    public function score(): Score
    {
        if ($this->isEmpty()) {
            return new Score(0.0);
        }

        /** @var float $score */
        $score = $this->sum(fn (Coincidence $match) => $match->score()->value());

        return new Score(min(1.0, $score));
    }

    public function confidence(): Confidence
    {
        if ($this->isEmpty()) {
            return new Confidence(0.0);
        }

        return new Confidence(
            (float) $this->average(fn (Coincidence $match) => $match->confidence()->value())
        );
    }

    public function offensive(): bool
    {
        /** @var float $threshold */
        $threshold = config('censor.threshold_score', 0.5);

        return $this->isNotEmpty() && ($this->score()->value() >= $threshold);
    }

    /**
     * @return array<string>
     */
    public function words(): array
    {
        // @phpstan-ignore return.type
        return $this->map(fn (Coincidence $match) => $match->word())->toArray();
    }

    /**
     * @param  MatchCollection  $items
     */
    public function merge($items): self
    {
        foreach ($items as $item) {
            if (! $this->contains(fn (Coincidence $existingItem) => $existingItem->word() === $item->word())) {
                $this->add($item);
            }
        }

        return $this;
    }

    public function clean(string $text): string
    {
        if ($this->isEmpty()) {
            return $text;
        }

        /** @var array<array{start: int, length: int}> $positions */
        $positions = [];

        foreach ($this as $match) {
            $pos = 0;
            while (($pos = mb_stripos($text, $match->word(), $pos)) !== false) {
                $positions[] = [
                    'start' => $pos,
                    'length' => mb_strlen($match->word()),
                ];
                $pos += mb_strlen($match->word());
            }
        }

        usort($positions, function ($a, $b) {
            return $b['start'] - $a['start'];
        });

        $result = $text;
        /** @var string $replacer */
        $replacer = config('censor.mask_char', '*');

        foreach ($positions as $position) {
            $replacement = str_repeat($replacer, $position['length']);
            $result = mb_substr($result, 0, $position['start']).
                $replacement.
                mb_substr($result, $position['start'] + $position['length']);
        }

        return $result;
    }
}
