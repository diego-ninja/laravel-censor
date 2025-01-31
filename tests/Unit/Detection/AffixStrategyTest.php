<?php

namespace Tests\Unit\Detection;

use Ninja\Censor\Detection\Strategy\AffixStrategy;
use Ninja\Censor\Enums\MatchType;

test('word variant strategy detects suffixes', function () {
    $suffixes = config('censor.suffixes');
    $prefixes = config('censor.prefixes');

    $strategy = new AffixStrategy($prefixes, $suffixes);
    $variations = [
        'fucking',
        'fucked',
        'fucker',
        'fuckers',
        'fucks',
    ];

    foreach ($variations as $text) {
        $result = $strategy->detect($text, ['fuck']);
        expect($result)
            ->toHaveCount(1)
            ->sequence(
                fn ($match) => $match
                    ->word()->toBe($text)
                    ->type()->toBe(MatchType::Variation)
            );
    }
});

test('word variant strategy detects prefixes', function () {
    $suffixes = config('censor.suffixes');
    $prefixes = config('censor.prefixes');

    $strategy = new AffixStrategy($prefixes, $suffixes);
    $variations = [
        'unfuck',
        'refuck',
        'superfuck',
        'antifuck',
        'outfuck',
    ];

    foreach ($variations as $text) {
        $result = $strategy->detect($text, ['fuck']);
        expect($result)
            ->toHaveCount(1)
            ->sequence(
                fn ($match) => $match
                    ->word()->toBe($text)
                    ->type()->toBe(MatchType::Variation)
            );
    }
});

test('word variant strategy detects prefix-suffix combinations', function () {
    $suffixes = config('censor.suffixes');
    $prefixes = config('censor.prefixes');

    $strategy = new AffixStrategy($prefixes, $suffixes);
    $variations = [
        'unfucking',
        'refucked',
        'superfucker',
        'antifuckers',
        'outfucked',
    ];

    foreach ($variations as $text) {
        $result = $strategy->detect($text, ['fuck']);
        expect($result)
            ->toHaveCount(1)
            ->sequence(
                fn ($match) => $match
                    ->word()->toBe($text)
                    ->type()->toBe(MatchType::Variation)
            );
    }
});

test('word variant strategy handles special ending rules', function () {
    $suffixes = config('censor.suffixes');
    $prefixes = config('censor.prefixes');

    $strategy = new AffixStrategy($prefixes, $suffixes);

    $result = $strategy->detect('bitchiest', ['bitchy']);
    expect($result)
        ->toHaveCount(1)
        ->and($result->first()->word())->toBe('bitchiest');

    $result = $strategy->detect('shitting', ['shit']);
    expect($result)
        ->toHaveCount(1)
        ->and($result->first()->word())->toBe('shitting');
});

test('word variant strategy preserves case', function () {
    $suffixes = config('censor.suffixes');
    $prefixes = config('censor.prefixes');

    $strategy = new AffixStrategy($prefixes, $suffixes);

    $text = 'UnFucking REFUCKED superFUCKER';
    $result = $strategy->detect($text, ['fuck']);

    expect($result)
        ->toHaveCount(3)
        ->sequence(
            fn ($match) => $match->word()->toBe('UnFucking'),
            fn ($match) => $match->word()->toBe('REFUCKED'),
            fn ($match) => $match->word()->toBe('superFUCKER')
        );
});

test('word variant strategy handles multiple words in text', function () {
    $suffixes = config('censor.suffixes');
    $prefixes = config('censor.prefixes');

    $strategy = new AffixStrategy($prefixes, $suffixes);

    $text = 'unfucking shitting superfucker';
    $result = $strategy->detect($text, ['fuck', 'shit']);

    expect($result)->toHaveCount(3);
});
