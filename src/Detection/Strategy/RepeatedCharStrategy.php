<?php

namespace Ninja\Censor\Detection\Strategy;

use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Collections\OccurrenceCollection;
use Ninja\Censor\Enums\MatchType;
use Ninja\Censor\Support\Calculator;
use Ninja\Censor\ValueObject\Coincidence;
use Ninja\Censor\ValueObject\Position;

final class RepeatedCharStrategy extends AbstractStrategy
{
    public function detect(string $text, iterable $words): MatchCollection
    {
        $matches = new MatchCollection;

        foreach ($words as $badWord) {
            if (! $this->hasRepeatedChars($text)) {
                continue;
            }

            $pattern = $this->createPattern($badWord);
            if (preg_match_all($pattern, $text, $found, PREG_OFFSET_CAPTURE) !== false) {
                foreach ($found[0] as [$match, $offset]) {
                    if ($this->hasRepeatedChars($match)) {
                        $occurrences = new OccurrenceCollection([
                            new Position($offset, mb_strlen($match)),
                        ]);

                        $matches->addCoincidence(
                            new Coincidence(
                                word: $match,
                                type: MatchType::Repeated,
                                score: Calculator::score($text, $match, MatchType::Repeated, $occurrences),
                                confidence: Calculator::confidence($text, $match, MatchType::Repeated, $occurrences),
                                occurrences: $occurrences,
                                context: ['original' => $badWord]
                            )
                        );
                    }
                }
            }
        }

        return $matches;
    }

    private function createPattern(string $word): string
    {
        $pattern = '/\b';
        foreach (str_split($word) as $char) {
            $pattern .= preg_quote($char, '/').'+';
        }

        return $pattern.'\b/iu';
    }

    private function hasRepeatedChars(string $text): bool
    {
        return (bool) preg_match('/(.)\1+/u', $text);
    }

    public function weight(): float
    {
        return MatchType::Repeated->weight();
    }
}
