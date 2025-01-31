<?php

namespace Ninja\Censor\Services\Pipeline;

use Ninja\Censor\Result\AbstractResult;
use Ninja\Censor\Result\Builder\ResultBuilder;
use Ninja\Censor\Services\Contracts\ServiceResponse;
use Ninja\Censor\Services\Pipeline\Stage\AbstractStage;

final class TransformationPipeline
{
    /** @var array<AbstractStage> */
    private array $stages = [];

    public function addStage(AbstractStage $stage): self
    {
        $clone = clone $this;
        $clone->stages[] = $stage;

        return $clone;
    }

    public function process(ServiceResponse $response): AbstractResult
    {
        $builder = new ResultBuilder;
        $builder = $builder->withOriginalText($response->original());

        foreach ($this->stages as $stage) {
            $builder = $stage->transform($response, $builder);
        }

        return $builder->build();
    }
}
