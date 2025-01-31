<?php

namespace Tests\Unit\Detection;

use Ninja\Censor\Detection\Strategy\VariationStrategy;
use Ninja\Censor\Enums\MatchType;

test('variation strategy detects separated characters', function () {
    $strategy = new VariationStrategy(true);
    $result = $strategy->detect('f u c k this', ['fuck']);

    expect($result)
        ->toHaveCount(1)
        ->sequence(
            fn ($match) => $match
                ->word()->toBe('f u c k')
                ->type()->toBe(MatchType::Variation)
        );

});

test('variation strategy handles multiple separators', function () {
    $strategy = new VariationStrategy(false);
    $variations = [
        'f.u.c.k',
        'f-u-c-k',
        'f_u_c_k',
    ];

    foreach ($variations as $text) {
        $result = $strategy->detect($text, ['fuck']);

        expect($result)
            ->toHaveCount(1);
    }
});

test('variation strategy handles multiple spaces between characters', function () {
    $strategy = new VariationStrategy(true);
    $result = $strategy->detect('f  u  c  k', ['fuck']);

    expect($result)
        ->toHaveCount(1);
});

test('variation strategy preserves other words', function () {
    $strategy = new VariationStrategy(false);
    $result = $strategy->detect('this f.u.c.k test', ['fuck']);

    expect($result)
        ->toHaveCount(1);
});
