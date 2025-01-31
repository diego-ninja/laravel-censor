<?php

namespace Ninja\Censor\Services\Pipeline\Stage;

use Ninja\Censor\Result\Contracts\ResultBuilder;
use Ninja\Censor\Services\Contracts\ServiceResponse;

class OffensiveStage extends AbstractStage
{
    public function transform(ServiceResponse $response, ResultBuilder $builder): ResultBuilder
    {
        if ($response->matches() !== null) {
            return $builder->withOffensive($response->matches()->offensive());
        }

        return $builder->withOffensive(false);
    }
}
