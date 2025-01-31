<?php

namespace Ninja\Censor\Services\Adapters;

use InvalidArgumentException;
use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Collections\OccurrenceCollection;
use Ninja\Censor\Enums\Category;
use Ninja\Censor\Enums\MatchType;
use Ninja\Censor\Services\Contracts\ServiceResponse;
use Ninja\Censor\Support\Calculator;
use Ninja\Censor\ValueObject\Coincidence;
use Ninja\Censor\ValueObject\Confidence;
use Ninja\Censor\ValueObject\Position;
use Ninja\Censor\ValueObject\Score;
use Ninja\Censor\ValueObject\Sentiment;

/**
 * @immutable
 */
final readonly class TisaneAdapter extends AbstractAdapter
{
    /**
     * @param array{
     *   text: string,
     *   sentiment: float,
     *   abuse?: array<array{
     *     type: string,
     *     severity: string,
     *     text: string,
     *     offset: int,
     *     length: int
     *   }>,
     * } $response
     */
    public function adapt(string $text, array $response): ServiceResponse
    {
        $matches = new MatchCollection;
        $categories = [];

        foreach ($response['abuse'] ?? [] as $abuse) {
            $occurrences = new OccurrenceCollection([
                new Position($abuse['offset'], $abuse['length']),
            ]);

            try {
                $categories[] = Category::fromTisane($abuse['type']);
            } catch (InvalidArgumentException) {
                continue;
            }

            $matches->addCoincidence(
                new Coincidence(
                    word: $abuse['text'],
                    type: MatchType::Exact,
                    score: Calculator::score($text, $abuse['text'], MatchType::Exact, $occurrences),
                    confidence: Calculator::confidence($text, $abuse['text'], MatchType::Exact, $occurrences),
                    occurrences: $occurrences,
                    context: [
                        'type' => $abuse['type'],
                        'severity' => $abuse['severity'],
                    ]
                )
            );

        }

        $sentiment = $this->createSentiment($response['sentiment']);
        $score = $this->calculateScore($matches, $sentiment);

        return new class($text, $matches, $score, $sentiment, $categories) implements ServiceResponse
        {
            /**
             * @param  array<Category>  $categories
             */
            public function __construct(
                private readonly string $original,
                private readonly MatchCollection $matches,
                private readonly Score $score,
                private readonly Sentiment $sentiment,
                private readonly array $categories
            ) {}

            public function original(): string
            {
                return $this->original;
            }

            public function replaced(): string
            {
                return $this->matches->clean($this->original);
            }

            public function matches(): MatchCollection
            {
                return $this->matches;
            }

            public function score(): Score
            {
                return $this->score;
            }

            public function confidence(): Confidence
            {
                return $this->matches->confidence();
            }

            /** @return array<Category> */
            public function categories(): array
            {
                return $this->categories;
            }

            public function sentiment(): Sentiment
            {
                return $this->sentiment;
            }
        };
    }

    private function calculateScore(MatchCollection $matches, Sentiment $sentiment): Score
    {
        if (! $matches->isEmpty()) {
            $matchScore = $matches->score()->value();

            $sentimentModifier = match (true) {
                $sentiment->value() < 0 => abs($sentiment->value()) * 0.2,
                $sentiment->value() > 0 => -($sentiment->value() * 0.1),
                default => 0
            };

            return new Score(min(1.0, $matchScore + $sentimentModifier));
        }

        if ($sentiment->value() < -0.7) {
            return new Score(abs($sentiment->value()) * 0.5);
        }

        return new Score(0.0);
    }

}
