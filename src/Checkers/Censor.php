<?php

namespace Ninja\Censor\Checkers;

use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Contracts\Processor;
use Ninja\Censor\Contracts\ProfanityChecker;
use Ninja\Censor\Contracts\Result;
use Ninja\Censor\Result\AbstractResult;
use Ninja\Censor\Result\Builder\ResultBuilder;
use Ninja\Censor\Support\PatternGenerator;
use Ninja\Censor\Support\TextCleaner;
use Ninja\Censor\ValueObject\Confidence;
use Ninja\Censor\ValueObject\Score;

final class Censor implements ProfanityChecker
{
    private const CHUNK_SIZE = 1000;

    private bool $fullWords = false;

    public function __construct(
        private readonly PatternGenerator $generator,
        private readonly Processor $processor
    ) {}

    public function check(string $text): Result
    {
        if (mb_strlen($text) < self::CHUNK_SIZE) {
            return $this->processor->process([$text])[0];
        }

        $chunks = $this->split($text);
        $results = $this->processor->process($chunks);

        return $this->mergeResults($results, $text);
    }

    public function setFullWords(bool $fullWords): self
    {
        $this->fullWords = $fullWords;

        return $this;
    }

    /**
     * @return array<string>
     */
    private function split(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (! $sentences) {
            return [$text];
        }

        $chunks = [];
        $currentChunk = '';

        foreach ($sentences as $sentence) {
            if (mb_strlen($currentChunk.$sentence) > self::CHUNK_SIZE) {
                $chunks[] = trim($currentChunk);
                $currentChunk = $sentence;
            } else {
                $currentChunk .= ' '.$sentence;
            }
        }

        if (! empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    /**
     * @param  array<AbstractResult>  $results
     */
    private function mergeResults(array $results, string $originalText): Result
    {
        $matches = new MatchCollection;
        $processedWords = [];

        foreach ($results as $result) {
            $matches = $matches->merge($result->matches());
            $processedWords = array_merge($processedWords, $result->words());
        }

        return (new ResultBuilder)
            ->withOriginalText($originalText)
            ->withWords(array_unique($processedWords))
            ->withReplaced($matches->clean($originalText))
            ->withScore($matches->score($originalText))
            ->withOffensive(! $matches->isEmpty())
            ->withConfidence($matches->confidence())
            ->build();
    }
}
