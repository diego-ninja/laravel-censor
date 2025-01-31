<?php

namespace Ninja\Censor\Processors;

use Laravel\Octane\Facades\Octane;
use Ninja\Censor\Result\AbstractResult;

final class OctaneProcessor extends AbstractProcessor
{
    public function process(array $chunks): array
    {
        $indexedTasks = [];
        foreach ($chunks as $index => $chunk) {
            $indexedTasks[$index] = fn () => [
                'index' => $index,
                'result' => $this->processChunk($chunk),
            ];
        }

        /** @var array<array{index: int, result: AbstractResult}> $results */
        $results = Octane::concurrently($indexedTasks);
        usort($results, fn ($a, $b) => $a['index'] <=> $b['index']);
        $ordered = array_map(fn ($item) => $item['result'], $results);

        return [$this->merge($ordered)];
    }
}
