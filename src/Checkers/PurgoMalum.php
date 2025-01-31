<?php

namespace Ninja\Censor\Checkers;

use GuzzleHttp\ClientInterface;
use Ninja\Censor\Result\Contracts\Result;
use Ninja\Censor\Services\Contracts\ServiceAdapter;
use Ninja\Censor\Services\Pipeline\TransformationPipeline;

final class PurgoMalum extends AbstractProfanityChecker
{
    public function __construct(
        private readonly ServiceAdapter $adapter,
        private readonly TransformationPipeline $pipeline,
        protected ?ClientInterface $client = null
    ) {
        parent::__construct($client);
    }

    protected function baseUri(): string
    {
        return 'https://www.purgomalum.com/service/';
    }

    public function check(string $text): Result
    {
        $response = $this->get('json', [
            'text' => $text,
            'fill_char' => config('censor.mask_char'),
        ]);

        return $this->pipeline->process(
            $this->adapter->adapt($text, $response)
        );
    }
}
