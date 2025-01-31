<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Ninja\Censor\Checkers\PurgoMalum;
use Ninja\Censor\Services\Adapters\PurgoMalumAdapter;
use Ninja\Censor\Services\Pipeline\TransformationPipeline;

test('purgomalum detects offensive content', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'result' => '**** you ****',
        ])),
    ]);

    $client = new Client(['handler' => HandlerStack::create($mock)]);
    $checker = new PurgoMalum(
        adapter: new PurgoMalumAdapter,
        pipeline: app(TransformationPipeline::class),
        client: $client
    );

    $result = $checker->check('fuck you shit');

    expect($result)
        ->toBeOffensive()
        ->and($result->replaced())->toBe('**** you ****')
        ->and($result->original())->toBe('fuck you shit')
        ->and($result->score()->value())->toBe(1.0)
        ->and($result->confidence()->value())->toBe(1.0)
        ->and($result->matches())->toHaveCount(2);

});

test('purgomalum handles clean content', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'result' => 'clean text here',
        ])),
    ]);

    $client = new Client(['handler' => HandlerStack::create($mock)]);
    $checker = new PurgoMalum(
        adapter: new PurgoMalumAdapter,
        pipeline: app(TransformationPipeline::class),
        client: $client
    );

    $result = $checker->check('clean text here');

    expect($result)
        ->toBeClean()
        ->and($result->replaced())->toBe('clean text here')
        ->and($result->matches())->toBeEmpty()
        ->and($result->score()->value())->toBe(0.0);
});
