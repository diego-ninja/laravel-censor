<?php

namespace Ninja\Censor\Result;

use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Enums\MatchType;
use Ninja\Censor\Result\Builder\ResultBuilder;
use Ninja\Censor\Support\Calculator;
use Ninja\Censor\ValueObject\Coincidence;

final class PurgoMalumResult extends AbstractResult
{
    public static function fromResponse(string $text, array $response): AbstractResult
    {
        /**
         * @var array{
         *     result: string
         * } $response
         */
        $builder = new ResultBuilder;
        $matches = self::extractMatches($text, $response['result']);

        return $builder
            ->withOriginalText($text)
            ->withOffensive($text !== $response['result'])
            ->withWords($matches->words())
            ->withMatches($matches)
            ->withScore($matches->score())
            ->withConfidence($matches->confidence())
            ->withReplaced($response['result'])
            ->build();
    }

    private static function extractMatches(string $original, string $replaced): MatchCollection
    {
        $matches = new MatchCollection;
        $originalWords = explode(' ', $original);
        $replacedWords = explode(' ', $replaced);

        foreach ($originalWords as $i => $word) {
            if (isset($replacedWords[$i]) && $word !== $replacedWords[$i]) {
                $matches->add(new Coincidence(
                    word: $word,
                    type: MatchType::Exact,
                    score: Calculator::score($word, $original, MatchType::Exact),
                    confidence: Calculator::confidence($word, $original, MatchType::Exact),
                    context: ['original' => $word, 'replaced' => $replacedWords[$i]]
                ));
            }
        }

        return $matches;
    }
}
