<?php

namespace Tests\Unit\Detection;

use Ninja\Censor\Detection\Strategy\NGramStrategy;
use Ninja\Censor\Enums\MatchType;

test('ngram strategy detects offensive phrases', function () {
    $strategy = new NGramStrategy;
    $phrase = 'son of a bitch';
    $words = ['son of a bitch']; // La frase completa debe estar en el diccionario

    $result = $strategy->detect($phrase, $words);

    expect($result)
        ->toHaveCount(1)
        ->sequence(
            fn ($match) => $match
                ->word()->toBe('son of a bitch')
                ->type()->toBe(MatchType::NGram)
        );

});

test('ngram strategy only matches complete phrases', function () {
    $strategy = new NGramStrategy;
    $result = $strategy->detect('this piece of text', ['piece of cake']);

    expect($result)->toBeEmpty();
});

test('ngram strategy handles case insensitive matches', function () {
    $strategy = new NGramStrategy;
    $words = ['son of a bitch']; // Frase en el diccionario
    $result = $strategy->detect('Son Of A Bitch', $words);

    expect($result)
        ->toHaveCount(1);
});

test('ngram strategy ignores single words', function () {
    $strategy = new NGramStrategy;
    $result = $strategy->detect('bad word', ['bad']);

    expect($result)->toBeEmpty();
});

test('ngram strategy handles overlapping phrases correctly', function () {
    $strategy = new NGramStrategy;
    $text = 'piece of shit happens';
    $words = [
        'piece of shit',
        'shit happens',
    ];

    $result = $strategy->detect($text, $words);

    expect($result)
        ->toHaveCount(2)
        ->sequence(
            fn ($match) => $match->word()->toBe('piece of shit'),
            fn ($match) => $match->word()->toBe('shit happens')
        );
});

test('ngram strategy only matches phrases from dictionary', function () {
    $strategy = new NGramStrategy;
    $text = 'this is a random combination of words';
    $words = ['specific phrase', 'another phrase'];

    $result = $strategy->detect($text, $words);

    expect($result)->toBeEmpty();
});

test('ngram strategy handles phrases with special characters', function () {
    $strategy = new NGramStrategy;
    $text = "What's your problem?";
    $words = ["what's your problem"];

    $result = $strategy->detect($text, $words);

    expect($result)
        ->toHaveCount(1);
});

test('ngram strategy preserves punctuation and spacing', function () {
    $strategy = new NGramStrategy;
    $text = 'Well, son of a bitch!';
    $words = ['son of a bitch'];

    $result = $strategy->detect($text, $words);

    expect($result)
        ->toHaveCount(1);
});
