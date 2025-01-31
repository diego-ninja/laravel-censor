<?php

namespace Ninja\Censor\ValueObject;

use Ninja\Censor\Collections\OccurrenceCollection;
use Ninja\Censor\Enums\MatchType;

/**
 * @immutable
 */
final readonly class Coincidence
{
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        private string $word,
        private MatchType $type,
        private Score $score,
        private Confidence $confidence,
        private OccurrenceCollection $occurrences,
        private ?array $context = null,
    ) {}

    public function word(): string
    {
        return $this->word;
    }

    public function type(): MatchType
    {
        return $this->type;
    }

    public function score(): Score
    {
        return $this->score;
    }

    public function confidence(): Confidence
    {
        return $this->confidence;
    }

    public function occurrences(): OccurrenceCollection
    {
        return $this->occurrences;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function context(): ?array
    {
        return $this->context;
    }

    public function clean(string $text): string
    {
        /** @var string $replacer */
        $replacer = config('censor.mask_char', '*');

        return $this->occurrences->apply($text, $replacer);
    }
}
