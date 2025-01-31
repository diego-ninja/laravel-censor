<?php

namespace Ninja\Censor\Checkers;

use Ninja\Censor\Checkers\Contracts\ProfanityChecker;
use Ninja\Censor\Processors\Contracts\Processor;
use Ninja\Censor\Result\Contracts\Result;
use Ninja\Censor\Services\Contracts\ServiceAdapter;
use Ninja\Censor\Services\Pipeline\TransformationPipeline;

final class Censor implements ProfanityChecker
{
    private const CHUNK_SIZE = 500;

    public function __construct(
        private readonly Processor $processor,
        private readonly ServiceAdapter $adapter,
        private readonly TransformationPipeline $pipeline
    ) {}

    public function check(string $text): Result
    {
        if (mb_strlen($text) < self::CHUNK_SIZE) {
            $processorResult = $this->processor->process([$text])[0];

            $adaptedResult = $this->adapter->adapt($text, [
                'result' => $processorResult,
            ]);

            return $this->pipeline->process($adaptedResult);
        }

        $chunks = $this->split($text);
        $results = $this->processor->process($chunks);

        $adaptedResult = $this->adapter->adapt($text, [
            'result' => $results[0],
        ]);

        return $this->pipeline->process($adaptedResult);
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
}
