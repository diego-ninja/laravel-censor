<?php

namespace Ninja\Censor\Result\Builder;

use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Enums\Category;
use Ninja\Censor\Result\AbstractResult;
use Ninja\Censor\Result\Contracts\ResultBuilder as ResultBuilderContract;
use Ninja\Censor\ValueObject\Confidence;
use Ninja\Censor\ValueObject\Score;
use Ninja\Censor\ValueObject\Sentiment;
use RuntimeException;

final class ResultBuilder implements ResultBuilderContract
{
    private string $original = '';

    private bool $offensive = false;

    /**
     * @var array<string>
     */
    private array $words = [];

    private string $replaced = '';

    private ?Score $score = null;

    private ?Confidence $confidence = null;

    private ?Sentiment $sentiment = null;

    /**
     * @var array<Category>|null
     */
    private ?array $categories = null;

    private ?MatchCollection $matches = null;

    public function withOriginalText(string $text): self
    {
        $clone = clone $this;
        $clone->original = $text;
        $clone->replaced = $text;

        return $clone;
    }

    public function build(): AbstractResult
    {
        return new class(offensive: $this->offensive, words: $this->words, replaced: $this->replaced, original: $this->original, matches: $this->matches, score: $this->score, confidence: $this->confidence, sentiment: $this->sentiment, categories: $this->categories) extends AbstractResult
        {
            public static function fromResponse(string $text, array $response): AbstractResult
            {
                throw new RuntimeException('Not supported in anonymous class');
            }
        };
    }

    public function withOffensive(bool $offensive): ResultBuilderContract
    {
        $clone = clone $this;
        $clone->offensive = $offensive;

        return $clone;
    }

    public function withWords(array $words): ResultBuilderContract
    {
        $clone = clone $this;
        $clone->words = $words;

        return $clone;
    }

    public function withReplaced(string $replaced): ResultBuilderContract
    {
        $clone = clone $this;
        $clone->replaced = $replaced;

        return $clone;
    }

    public function withScore(?Score $score): ResultBuilderContract
    {
        $clone = clone $this;
        $clone->score = $score;

        return $clone;
    }

    public function withConfidence(?Confidence $confidence): ResultBuilderContract
    {
        $clone = clone $this;
        $clone->confidence = $confidence;

        return $clone;
    }

    public function withSentiment(?Sentiment $sentiment): ResultBuilderContract
    {
        $clone = clone $this;
        $clone->sentiment = $sentiment;

        return $clone;
    }

    public function withCategories(?array $categories): ResultBuilderContract
    {
        $clone = clone $this;
        $clone->categories = $categories;

        return $clone;
    }

    public function withMatches(MatchCollection $matches): ResultBuilderContract
    {
        $clone = clone $this;
        $clone->matches = $matches;

        return $clone;
    }
}
