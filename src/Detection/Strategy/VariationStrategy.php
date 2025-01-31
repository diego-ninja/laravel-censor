<?php

namespace Ninja\Censor\Detection\Strategy;

use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Collections\OccurrenceCollection;
use Ninja\Censor\Enums\MatchType;
use Ninja\Censor\Support\Calculator;
use Ninja\Censor\Support\TextAnalyzer;
use Ninja\Censor\ValueObject\Coincidence;
use Ninja\Censor\ValueObject\Position;

final class VariationStrategy extends AbstractStrategy
{
    public function __construct(private readonly bool $fullWords = true) {}

    public function detect(string $text, iterable $words): MatchCollection
    {
        $matches = new MatchCollection;

        foreach ($words as $word) {
            if ($this->fullWords) {
                $this->detectFullWords($text, $word, $matches);
            } else {
                $this->detectPartialWords($text, $word, $matches);
            }
        }

        return $matches;
    }

    private function detectFullWords(string $text, string $word, MatchCollection $matches): void
    {
        $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return;
        }

        $pattern = '/\b'.implode('[\s\.\-_\/]*', array_map(
            fn ($c) => preg_quote($c, '/'),
            $chars
        )).'\b/iu';

        if (preg_match_all($pattern, $text, $found, PREG_OFFSET_CAPTURE) !== false) {
            foreach ($found[0] as [$match, $offset]) {
                $occurrences = new OccurrenceCollection([
                    new Position($offset, mb_strlen($match)),
                ]);

                $matches->addCoincidence(
                    new Coincidence(
                        word: $match,
                        type: MatchType::Variation,
                        score: Calculator::score($text, $match, MatchType::Variation, $occurrences),
                        confidence: Calculator::confidence($text, $match, MatchType::Variation, $occurrences),
                        occurrences: $occurrences,
                        context: ['original' => $word, 'full_word' => true]
                    )
                );
            }
        }
    }

    private function detectPartialWords(string $text, string $word, MatchCollection $matches): void
    {
        foreach (TextAnalyzer::getSeparatorVariations($word) as $variation) {
            if (str_contains($variation, ' ')) {
                continue;
            }

            $positions = [];
            $pos = 0;
            while (($pos = mb_stripos($text, $variation, $pos)) !== false) {
                $positions[] = new Position($pos, mb_strlen($variation));
                $pos += mb_strlen($variation);
            }

            if (! empty($positions)) {
                $occurrences = new OccurrenceCollection($positions);

                $matches->addCoincidence(
                    new Coincidence(
                        word: $variation,
                        type: MatchType::Variation,
                        score: Calculator::score($text, $variation, MatchType::Variation, $occurrences),
                        confidence: Calculator::confidence($text, $variation, MatchType::Variation, $occurrences),
                        occurrences: $occurrences,
                        context: ['original' => $word, 'full_word' => false]
                    )
                );
            }
        }
    }

    public function weight(): float
    {
        return MatchType::Variation->weight();
    }
}
