<?php

namespace Ninja\Censor\Services\Pipeline\Stage;

use Ninja\Censor\Result\Contracts\ResultBuilder;
use Ninja\Censor\Services\Contracts\ServiceResponse;

abstract class AbstractStage
{
    abstract public function transform(
        ServiceResponse $response,
        ResultBuilder $builder
    ): ResultBuilder;
}