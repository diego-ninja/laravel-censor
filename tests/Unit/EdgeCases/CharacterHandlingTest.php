<?php

namespace Tests\Unit\EdgeCases;

use Ninja\Censor\Checkers\Censor;

test('handles unicode characters correctly', function () {
    $censor = app(Censor::class);

    $texts = [
        'fūćk' => 'fūćk',
        'シット' => 'シット',
        'мат' => 'мат',
        'f♥ck' => '****',
        'sh!t' => '****',
        'fück' => '****',
        'fûck' => '****',
    ];

    foreach ($texts as $input => $expected) {
        $result = $censor->check($input)->replaced();
        expect($result)->toBe($expected, "Failed asserting that '$input' is censored as '$expected', got '$result'");
    }
});

test('handles emojis correctly', function () {
    $censor = app(Censor::class);

    $texts = [
        'fuck 🤬' => '**** 🤬',
        '🤬 shit 🤬' => '🤬 **** 🤬',
        '💩 crap 💩' => '💩 **** 💩',
    ];

    foreach ($texts as $input => $expected) {
        $result = $censor->check($input)->replaced();
        expect($result)->toBe($expected, "Failed asserting that '$input' is censored as '$expected', got '$result'");
    }
});

test('handles mixed case with accents correctly', function () {
    $censor = app(Censor::class);

    $texts = [
        'FüCk' => '****',
        'ShÏt' => '****',
        'crÄp' => '****',
    ];

    foreach ($texts as $input => $expected) {
        $result = $censor->check($input)->replaced();
        expect($result)->toBe($expected, "Failed asserting that '$input' is censored as '$expected', got '$result'");
    }
});

test('respects word boundaries with unicode', function () {
    $censor = app(Censor::class);

    $texts = [
        'scrapped' => 'scrapped',           // Should not censor 'crap'
        'räpeseed' => 'räpeseed',           // Should not censor 'rape'
        'classification' => 'classification',  // Should not censor 'ass'
    ];

    foreach ($texts as $input => $expected) {
        $result = $censor->check($input);
        expect($result->replaced())->toBe($expected, sprintf("Failed asserting that '%s' is preserved as '%s', got '%s'", $input, $expected, $result->replaced()));
    }
});
