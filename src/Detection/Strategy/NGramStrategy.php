<?php

namespace Ninja\Censor\Detection\Strategy;

use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Collections\OccurrenceCollection;
use Ninja\Censor\Enums\MatchType;
use Ninja\Censor\Support\Calculator;
use Ninja\Censor\ValueObject\Coincidence;
use Ninja\Censor\ValueObject\Position;

final class NGramStrategy extends AbstractStrategy
{
    public function detect(string $text, iterable $words): MatchCollection
    {
        $matches = new MatchCollection;
        $phrases = array_filter((array) $words, fn ($word) => str_contains($word, ' '));

        foreach ($phrases as $phrase) {
            $phrasePattern = preg_quote(mb_strtolower($phrase), '/');
            $pattern = '/\b'.$phrasePattern.'\b/iu';

            if (preg_match_all($pattern, $text, $found, PREG_OFFSET_CAPTURE) !== false) {
                foreach ($found[0] as [$match, $offset]) {
                    $occurrences = new OccurrenceCollection([
                        new Position($offset, mb_strlen($match)),
                    ]);

                    $matches->addCoincidence(
                        new Coincidence(
                            word: $match,
                            type: MatchType::NGram,
                            score: Calculator::score($text, $match, MatchType::NGram, $occurrences),
                            confidence: Calculator::confidence($text, $match, MatchType::NGram, $occurrences),
                            occurrences: $occurrences,
                            context: ['original' => $phrase]
                        )
                    );
                }
            }
        }

        return $matches;
    }

    public function weight(): float
    {
        return MatchType::NGram->weight();
    }
}
