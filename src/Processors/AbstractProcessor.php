<?php

namespace Ninja\Censor\Processors;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Collections\OccurrenceCollection;
use Ninja\Censor\Collections\StrategyCollection;
use Ninja\Censor\Detection\Contracts\DetectionStrategy;
use Ninja\Censor\Dictionary\LazyDictionary;
use Ninja\Censor\Processors\Contracts\Processor;
use Ninja\Censor\Result\AbstractResult;
use Ninja\Censor\Result\Builder\ResultBuilder;
use Ninja\Censor\Support\Calculator;
use Ninja\Censor\Support\TextNormalizer;
use Ninja\Censor\ValueObject\Coincidence;
use Ninja\Censor\ValueObject\Position;
use Ninja\Censor\Whitelist;

abstract class AbstractProcessor implements Processor
{
    protected StrategyCollection $strategies;

    protected string $replacer;

    /**
     * @throws CircularDependencyException
     * @throws BindingResolutionException
     */
    public function __construct(
        private readonly Whitelist $whitelist,
        private readonly LazyDictionary $dictionary
    ) {
        /** @var string $replaceChar */
        $replaceChar = config('censor.mask_char', '*');
        $this->replacer = $replaceChar;

        $this->initializeStrategies();
    }

    /**
     * @throws CircularDependencyException
     * @throws BindingResolutionException
     */
    protected function initializeStrategies(): void
    {
        /** @var array<class-string<DetectionStrategy>> $strategies */
        $strategies = config('censor.services.local.strategies', []);

        $this->strategies = new StrategyCollection;
        foreach ($strategies as $strategy) {
            /** @var DetectionStrategy $class */
            $class = app()->build($strategy);
            $this->strategies->addStrategy($class);
        }
    }

    protected function processChunk(string $chunk): AbstractResult
    {
        $whitelisted = $this->whitelist->prepare($chunk);
        $normalized = TextNormalizer::normalize($whitelisted);

        /** @var string[] $words */
        $words = iterator_to_array($this->dictionary->getWords());
        $matches = $this->strategies->detect($normalized, $words);

        if ($matches->isEmpty()) {
            return $this->buildResult($chunk, $normalized, $matches);
        }

        $cleaned = $matches->clean($normalized);
        $finalText = $this->whitelist->restore($cleaned);

        return $this->buildResult($chunk, $finalText, $matches);
    }

    /**
     * @param  array<AbstractResult>  $results
     */
    protected function merge(array $results): AbstractResult
    {
        $matches = new MatchCollection;
        $replaced = '';
        $original = implode('', array_map(fn ($r) => $r->original(), $results));

        foreach ($results as $result) {
            foreach ($result->matches() ?? new MatchCollection as $match) {
                $positions = [];
                $pos = 0;
                while (($pos = mb_stripos($original, $match->word(), $pos)) !== false) {
                    $positions[] = new Position($pos, mb_strlen($match->word()));
                    $pos += mb_strlen($match->word());
                }

                if (! empty($positions)) {
                    $occurrences = new OccurrenceCollection($positions);
                    $matches->addCoincidence(
                        new Coincidence(
                            word: $match->word(),
                            type: $match->type(),
                            score: Calculator::score($original, $match->word(), $match->type(), $occurrences),
                            confidence: Calculator::confidence($original, $match->word(), $match->type(), $occurrences),
                            occurrences: $occurrences,
                            context: $match->context()
                        )
                    );
                }
            }
            $replaced .= $result->replaced();
        }

        return $this->buildResult($original, $replaced, $matches);
    }

    private function buildResult(
        string $original,
        string $finalText,
        MatchCollection $matches
    ): AbstractResult {
        return (new ResultBuilder)
            ->withOriginalText($original)
            ->withReplaced($finalText)
            ->withWords(array_unique($matches->words()))
            ->withScore($matches->score())
            ->withOffensive($matches->offensive())
            ->withConfidence($matches->confidence())
            ->withMatches($matches)
            ->build();
    }
}
