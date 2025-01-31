<?php

namespace Ninja\Censor\Services\Pipeline\Stage;

use Ninja\Censor\Result\Contracts\ResultBuilder;
use Ninja\Censor\Services\Contracts\ServiceResponse;

final class MetadataStage extends AbstractStage
{
    public function transform(
        ServiceResponse $response,
        ResultBuilder $builder
    ): ResultBuilder {
        return $builder
            ->withConfidence($response->confidence())
            ->withCategories($response->categories())
            ->withSentiment($response->sentiment());
    }
}
