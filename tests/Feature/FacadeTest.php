<?php

namespace Tests\Feature;

use Ninja\Censor\Checkers\PurgoMalum;
use Ninja\Censor\Enums\Provider;
use Ninja\Censor\Facades\Censor;
use Ninja\Censor\Result\Contracts\Result;
use Ninja\Censor\Services\Adapters\PurgoMalumAdapter;
use Ninja\Censor\Services\Pipeline\TransformationPipeline;

test('facade check method returns result instance', function () {
    $result = Censor::check('test text');
    expect($result)->toBeInstanceOf(Result::class);
});

test('facade offensive method returns boolean', function () {
    $result = Censor::offensive('fuck this shit');
    expect($result)->toBeTrue();
});

test('facade clean method returns cleaned string', function () {
    $text = 'fuck this shit';
    $result = Censor::clean($text);

    expect($result)->toBe('**** this ****');
});

test('facade with method returns correct service result', function () {
    // Configure mock response for PurgoMalum
    $this->app->bind('purgomalum', function ($app) {
        return new PurgoMalum(
            new PurgoMalumAdapter,
            app(TransformationPipeline::class),
            $this->getMockedHttpClient([
                ['result' => '**** text'],
            ])
        );
    });

    $result = Censor::with(Provider::PurgoMalum, 'bad text');
    expect($result)
        ->toBeInstanceOf(Result::class)
        ->and($result->replaced())->toBe('**** text');
});

test('facade can switch between services while maintaining state', function () {
    // Test default service
    $result1 = Censor::check('fuck this shit');
    expect($result1->replaced())->toBe('**** this ****');

    // Test PurgoMalum
    $this->app->bind('purgomalum', function ($app) {
        return new PurgoMalum(
            new PurgoMalumAdapter,
            app(TransformationPipeline::class),
            $this->getMockedHttpClient([
                ['result' => '**** text'],
            ])
        );
    });

    $result2 = Censor::with(Provider::PurgoMalum, 'bad text');
    expect($result2->replaced())->toBe('**** text');

    // Test we can still use default service
    $result3 = Censor::check('fuck this shit');
    expect($result3->replaced())->toBe('**** this ****');
});

test('facade handles invalid input gracefully', function () {
    $result = Censor::check('');
    expect($result)
        ->toBeInstanceOf(Result::class)
        ->and($result->offensive())->toBeFalse()
        ->and($result->replaced())->toBe('');
});

test('facade respects configuration changes', function () {
    // Change mask character
    config(['censor.mask_char' => '#']);
    $result = Censor::check('fuck this shit');
    expect($result->replaced())->toBe('#### this ####');

    // Reset for other tests
    config(['censor.mask_char' => '*']);
});

test('facade is bound in container correctly', function () {
    expect(app('censor'))->toBeInstanceOf(\Ninja\Censor\Censor::class);
});
