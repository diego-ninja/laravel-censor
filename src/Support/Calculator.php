<?php

namespace Ninja\Censor\Support;

use Ninja\Censor\Collections\OccurrenceCollection;
use Ninja\Censor\Enums\MatchType;
use Ninja\Censor\ValueObject\Confidence;
use Ninja\Censor\ValueObject\Score;

final readonly class Calculator
{
    private const OCCURRENCE_MULTIPLIER = 0.2;
    private const MAX_OCCURRENCE_BOOST = 1.0;
    public static function score(string $text, string $word, MatchType $type, OccurrenceCollection $occurrences): Score
    {
        $words = explode(' ', $word);
        $total = count(explode(' ', $text));

        $typeWeight = match ($type) {
            MatchType::Exact => 2.0,
            MatchType::Trie => 1.8,
            MatchType::Pattern => 1.5,
            MatchType::NGram => 1.3,
            default => $type->weight()
        };

        $lengthMultiplier = count($words) > 1 ? 1.5 : 1.2;
        $densityMultiplier = min(2.0, 1 + $occurrences->density($total));
        $occurrenceBoost = min(
            self::MAX_OCCURRENCE_BOOST,
            ($occurrences->count() - 1) * self::OCCURRENCE_MULTIPLIER
        );

        $baseScore = ($typeWeight * $lengthMultiplier * count($words)) / max($total, 1);
        $boostedScore = $baseScore * (1 + $occurrenceBoost);

        return new Score(min(1.0, $boostedScore * $densityMultiplier));

    }

    public static function confidence(string $text, string $word, MatchType $type, OccurrenceCollection $occurrences): Confidence
    {
        $occurrenceBoost = min(0.3, ($occurrences->count() - 1) * 0.1);
        $baseConfidence = $type->weight();

        return new Confidence(min(1.0, $baseConfidence + $occurrenceBoost));
    }
}
