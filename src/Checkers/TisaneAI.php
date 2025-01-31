<?php

namespace Ninja\Censor\Checkers;

use GuzzleHttp\ClientInterface;
use Ninja\Censor\Exceptions\ClientException;
use Ninja\Censor\Result\Contracts\Result;
use Ninja\Censor\Services\Contracts\ServiceAdapter;
use Ninja\Censor\Services\Pipeline\TransformationPipeline;

final class TisaneAI extends AbstractProfanityChecker
{
    public function __construct(
        private readonly string $key,
        private readonly ServiceAdapter $adapter,
        private readonly TransformationPipeline $pipeline,
        protected ?ClientInterface $client = null)
    {
        parent::__construct($client);
    }

    protected function baseUri(): string
    {
        return 'https://api.tisane.ai/';
    }

    /**
     * @throws ClientException
     */
    public function check(string $text): Result
    {
        /** @var string[] $languages */
        $languages = config('censor.languages', ['en']);
        $response = $this->post(
            'parse',
            [
                'content' => $text,
                'language' => implode('|', $languages),
                'settings' => [
                    'snippets' => true,
                    'abuse' => true,
                    'sentiment' => true,
                    'document_sentiment' => true,
                    'profanity' => true,
                ],
            ],
            [
                'Content-Type' => 'application/json',
                'Ocp-Apim-Subscription-Key' => $this->key,
            ]
        );

        /**
         * @var array{
         *   text: string,
         *   sentiment: float,
         *   topics?: array<string>,
         *   abuse?: array<array{
         *     type: string,
         *     severity: string,
         *     text: string,
         *     offset: int,
         *     length: int,
         *     sentence_index: int,
         *     explanation?: string,
         *   }>,
         *   sentiment_expressions?: array<array{
         *     sentence_index: int,
         *     offset: int,
         *     length: int,
         *     text: string,
         *     polarity: string,
         *     targets: array<string>,
         *     reasons?: array<string>,
         *     explanation?: string
         *   }>
         * } $response
         */
        return $this->pipeline->process(
            $this->adapter->adapt($text, $response)
        );
    }
}
