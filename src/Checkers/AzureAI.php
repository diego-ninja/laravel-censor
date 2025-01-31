<?php

namespace Ninja\Censor\Checkers;

use GuzzleHttp\ClientInterface;
use Ninja\Censor\Result\AzureResult;
use Ninja\Censor\Result\Contracts\Result;
use Ninja\Censor\Services\Contracts\ServiceAdapter;
use Ninja\Censor\Services\Pipeline\TransformationPipeline;

final class AzureAI extends AbstractProfanityChecker
{
    public const DEFAULT_API_VERSION = '2024-09-01';

    public function __construct(
        private readonly string $endpoint,
        private readonly string $key,
        private readonly string $version,
        private readonly ServiceAdapter $adapter,
        private readonly TransformationPipeline $pipeline,
        ?ClientInterface $httpClient = null
    ) {
        parent::__construct($httpClient);
    }

    protected function baseUri(): string
    {
        return rtrim($this->endpoint, '/');
    }

    public function check(string $text): Result
    {
        $endpoint = sprintf('/content/safety/text:analyze?api-version=%s', $this->version);
        $response = $this->post($endpoint, [
            'text' => $text,
            'categories' => [
                'Hate',
                'SelfHarm',
                'Sexual',
                'Violence',
            ],
            'outputType' => 'FourSeverityLevels',
            'blocklistsEnabled' => true,
        ], [
            'Ocp-Apim-Subscription-Key' => $this->key,
            'Content-Type' => 'application/json',
        ]);

        return $this->pipeline->process(
            $this->adapter->adapt($text, $response)
        );
    }
}
