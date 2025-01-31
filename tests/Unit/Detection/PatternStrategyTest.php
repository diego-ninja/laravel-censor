<?php

namespace Tests\Unit\Detection;

use Ninja\Censor\Cache\MemoryPatternCache;
use Ninja\Censor\Detection\Strategy\PatternStrategy;
use Ninja\Censor\Dictionary\LazyDictionary;
use Ninja\Censor\Enums\MatchType;
use Ninja\Censor\Support\PatternGenerator;

test('pattern strategy detects exact matches', function () {
    $strategy = app()->build(PatternStrategy::class);
    $result = $strategy->detect('fuck this shit', ['fuck', 'shit']);

    expect($result)
        ->toHaveCount(2)
        ->sequence(
            fn ($match) => $match
                ->word()->toBe('fuck')
                ->type()->toBe(MatchType::Pattern),
            fn ($match) => $match
                ->word()->toBe('shit')
                ->type()->toBe(MatchType::Pattern)
        );

});

test('pattern strategy handles character substitutions', function () {
    $dic = app(LazyDictionary::class);
    $generator = new PatternGenerator(config('censor.replacements'), false);
    $generator->forWords(iterator_to_array($dic->getWords()));

    $strategy = new PatternStrategy(
        $generator,
        new MemoryPatternCache
    );

    $result = $strategy->detect('fvck this sh!t', ['fuck', 'shit']);

    expect($result)
        ->toHaveCount(2)
        ->sequence(
            fn ($match) => $match->word()->toBe('fvck'),
            fn ($match) => $match->word()->toBe('sh!t')
        );
});

test('pattern strategy respects word boundaries', function () {
    $strategy = app()->build(PatternStrategy::class);
    $result = $strategy->detect('class assignment', ['ass']);

    expect($result)->toBeEmpty();
});

test('pattern strategy handles empty patterns', function () {
    $strategy = app()->build(PatternStrategy::class);
    $result = $strategy->detect('some text', []);

    expect($result)->toBeEmpty();
});
