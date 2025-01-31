<?php

namespace Ninja\Censor\Services\Adapters;

use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Collections\OccurrenceCollection;
use Ninja\Censor\Enums\MatchType;
use Ninja\Censor\Services\Contracts\ServiceResponse;
use Ninja\Censor\Support\Calculator;
use Ninja\Censor\ValueObject\Coincidence;
use Ninja\Censor\ValueObject\Confidence;
use Ninja\Censor\ValueObject\Position;
use Ninja\Censor\ValueObject\Score;
use Ninja\Censor\ValueObject\Sentiment;

final readonly class PurgoMalumAdapter extends AbstractAdapter
{
    public function adapt(string $text, array $response): ServiceResponse
    {
        /** @var string $replacedText */
        $replacedText = $response['result'];

        $matches = $this->findDifferences($text, $replacedText);

        return new class($text, $replacedText, $matches) implements ServiceResponse
        {
            public function __construct(
                private readonly string $original,
                private readonly string $replaced,
                private readonly MatchCollection $matches
            ) {}

            public function original(): string
            {
                return $this->original;
            }

            public function replaced(): string
            {
                return $this->replaced;
            }

            public function matches(): MatchCollection
            {
                return $this->matches;
            }

            public function score(): Score
            {
                return $this->matches->score();
            }

            public function confidence(): Confidence
            {
                return $this->matches->confidence();
            }

            public function categories(): ?array
            {
                return null;
            }

            public function sentiment(): ?Sentiment
            {
                return null;
            }
        };
    }

    private function findDifferences(string $original, string $replaced): MatchCollection
    {
        $matches = new MatchCollection;
        $pattern = '/\*+/';

        if (preg_match_all($pattern, $replaced, $found, PREG_OFFSET_CAPTURE)) {
            foreach ($found[0] as [$match, $position]) {
                $length = strlen($match);
                $originalWord = substr($original, $position, $length);

                $occurrences = new OccurrenceCollection([
                    new Position($position, $length),
                ]);

                $matches->addCoincidence(
                    new Coincidence(
                        word: $originalWord,
                        type: MatchType::Exact,
                        score: Calculator::score($original, $originalWord, MatchType::Exact, $occurrences),
                        confidence: Calculator::confidence($original, $originalWord, MatchType::Exact, $occurrences),
                        occurrences: $occurrences,
                        context: ['replaced' => $match]
                    )
                );
            }
        }

        return $matches;
    }
}
