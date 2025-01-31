<?php

namespace Ninja\Censor\Detection\Strategy;

use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Collections\OccurrenceCollection;
use Ninja\Censor\Detection\OptimizedLevenshtein;
use Ninja\Censor\Enums\MatchType;
use Ninja\Censor\Support\Calculator;
use Ninja\Censor\ValueObject\Coincidence;
use Ninja\Censor\ValueObject\Position;

final class LevenshteinStrategy extends AbstractStrategy
{
    private int $threshold;

    public function __construct()
    {

        /** @var int $threshold */
        $threshold = config('censor.services.local.levenshtein_threshold', 1);
        $this->threshold = $threshold;
    }

    public function detect(string $text, iterable $words): MatchCollection
    {
        $matches = new MatchCollection;
        $dictionary = is_array($words) ? $words : iterator_to_array($words);
        $levenshtein = new OptimizedLevenshtein($dictionary);

        $textWords = preg_split('/\s+/', $text);
        if ($textWords === false) {
            return $matches;
        }

        foreach ($textWords as $textWord) {
            $similarWords = $levenshtein->findSimilar($textWord, $this->threshold);
            if (! empty($similarWords)) {
                $positions = [];
                $pos = 0;
                while (($pos = mb_stripos($text, $textWord, $pos)) !== false) {
                    $positions[] = new Position($pos, mb_strlen($textWord));
                    $pos += mb_strlen($textWord);
                }

                $occurrences = new OccurrenceCollection($positions);

                $matches->addCoincidence(
                    new Coincidence(
                        word: $textWord,
                        type: MatchType::Levenshtein,
                        score: Calculator::score($text, $textWord, MatchType::Levenshtein, $occurrences),
                        confidence: Calculator::confidence($text, $textWord, MatchType::Levenshtein, $occurrences),
                        occurrences: $occurrences,
                        context: ['similar_words' => $similarWords]
                    )
                );
            }
        }

        return $matches;
    }

    public function weight(): float
    {
        return MatchType::Levenshtein->weight();
    }
}
