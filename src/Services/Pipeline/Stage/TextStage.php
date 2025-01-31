<?php

namespace Ninja\Censor\Services\Pipeline\Stage;

use Ninja\Censor\Result\Contracts\ResultBuilder;
use Ninja\Censor\Services\Contracts\ServiceResponse;
use Ninja\Censor\Whitelist;

final class TextStage extends AbstractStage
{
    public function __construct(
        private readonly Whitelist $whitelist
    ) {}

    public function transform(ServiceResponse $response, ResultBuilder $builder): ResultBuilder
    {
        $replaced = $response->replaced();
        if ($replaced === null && $response->matches() !== null) {
            $replaced = $response->matches()->clean($response->original());
        }

        $replaced = $replaced ?? $response->original();
        $restored = $this->whitelist->restore($replaced);

        return $builder->withReplaced($restored);
    }
}
