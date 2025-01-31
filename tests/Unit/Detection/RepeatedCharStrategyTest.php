<?php

namespace Tests\Unit\Detection;

use Ninja\Censor\Detection\Strategy\RepeatedCharStrategy;
use Ninja\Censor\Enums\MatchType;

test('repeated chars strategy detects repeated characters', function () {
    $strategy = new RepeatedCharStrategy;
    $variations = [
        'fuuuck',
        'fuuuuck',
        'ffuucckk',
    ];

    foreach ($variations as $text) {
        $result = $strategy->detect($text, ['fuck']);
        expect($result)
            ->toHaveCount(1)
            ->sequence(
                fn ($match) => $match
                    ->word()->toBe($text)
                    ->type()->toBe(MatchType::Repeated)
            );
    }
});

test('repeated chars strategy preserves word boundaries', function () {
    $strategy = new RepeatedCharStrategy;
    $result = $strategy->detect('claaass', ['ass']);

    expect($result)->toBeEmpty();
});

test('repeated chars strategy handles multiple words', function () {
    $strategy = new RepeatedCharStrategy;
    $result = $strategy->detect('fuuuck this shiiit', ['fuck', 'shit']);

    expect($result)
        ->toHaveCount(2)
        ->sequence(
            fn ($match) => $match->word()->toBe('fuuuck'),
            fn ($match) => $match->word()->toBe('shiiit')
        );
});

test('repeated chars strategy ignores non-repeated characters', function () {
    $strategy = new RepeatedCharStrategy;
    $result = $strategy->detect('fuck', ['fuck']);

    expect($result)->toBeEmpty();
});
