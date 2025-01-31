<?php

namespace Ninja\Censor\Services\Pipeline\Stage;

use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Result\Contracts\ResultBuilder;
use Ninja\Censor\Services\Contracts\ServiceResponse;

final class MatchesStage extends AbstractStage
{
    public function transform(
        ServiceResponse $response,
        ResultBuilder $builder
    ): ResultBuilder {
        if ($response->matches() !== null) {
            return $builder
                ->withMatches($response->matches())
                ->withWords($response->matches()->words());
        }

        return $builder
            ->withMatches(new MatchCollection)
            ->withWords([]);
    }
}
