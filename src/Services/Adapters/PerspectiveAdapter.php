<?php

namespace Ninja\Censor\Services\Adapters;

use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Collections\OccurrenceCollection;
use Ninja\Censor\Enums\Category;
use Ninja\Censor\Enums\MatchType;
use Ninja\Censor\Services\Contracts\ServiceResponse;
use Ninja\Censor\ValueObject\Coincidence;
use Ninja\Censor\ValueObject\Confidence;
use Ninja\Censor\ValueObject\Position;
use Ninja\Censor\ValueObject\Score;
use Ninja\Censor\ValueObject\Sentiment;

final readonly class PerspectiveAdapter extends AbstractAdapter
{
    private const RELEVANT_ATTRIBUTES = [
        'TOXICITY' => 0.8,
        'SEVERE_TOXICITY' => 1.0,
        'THREAT' => 0.9,
        'PROFANITY' => 0.7,
    ];

    /**
     * @param array{
     *     attributeScores: array<string, array{
     *         spanScores?: array<array{
     *             begin: int,
     *             end: int,
     *             score: array{value: float}
     *         }>,
     *         summaryScore: array{
     *             value: float,
     *             confidence: float
     *         }
     *     }>
     * } $response
     */
    public function adapt(string $text, array $response): ServiceResponse
    {
        $matches = new MatchCollection;
        $maxScore = 0.0;
        $avgConfidence = 0.0;
        $categories = [];

        foreach ($response['attributeScores'] as $attribute => $data) {
            $weight = self::RELEVANT_ATTRIBUTES[$attribute] ?? 0.0;
            $score = $data['summaryScore']['value'] * $weight;

            if ($score > 0.5) {
                $categories[] = Category::fromPerspective($attribute);
            }

            $maxScore = max($maxScore, $score);
            $avgConfidence += $data['summaryScore']['confidence'] ?? 0.0;

            if (isset($data['spanScores'])) {
                foreach ($data['spanScores'] as $span) {
                    $length = $span['end'] - $span['begin'];
                    $word = mb_substr($text, $span['begin'], $length);

                    $occurrences = new OccurrenceCollection([
                        new Position($span['begin'], $length),
                    ]);

                    $matches->addCoincidence(
                        new Coincidence(
                            word: $word,
                            type: MatchType::Exact,
                            score: new Score($span['score']['value']),
                            confidence: new Confidence(($data['summaryScore']['confidence'] ?? 0.7)),
                            occurrences: $occurrences,
                            context: [
                                'attribute' => $attribute,
                                'score' => $span['score']['value'],
                            ]
                        )
                    );
                }
            }
        }

        $confidenceValue = ! empty($response['attributeScores'])
            ? $avgConfidence / count($response['attributeScores'])
            : 0.0;

        return new class($text, $matches, new Score($maxScore), new Confidence($confidenceValue), $categories) implements ServiceResponse
        {
            public function __construct(
                private readonly string $original,
                private readonly MatchCollection $matches,
                private readonly Score $score,
                private readonly Confidence $confidence,
                /** @var array<Category> */
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
                return $this->confidence;
            }

            /** @return array<Category> */
            public function categories(): array
            {
                return $this->categories;
            }

            public function sentiment(): ?Sentiment
            {
                return null;
            }
        };
    }
}
