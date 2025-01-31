<?php

namespace Tests\Unit\Support;

use Ninja\Censor\Dictionary\LazyDictionary;
use Ninja\Censor\Support\PatternGenerator;

test('pattern generator handles lazy dictionary correctly', function () {
    $words = ['test1', 'test2', 'test3'];
    $dictionary = LazyDictionary::withWords($words);

    $generator = PatternGenerator::withDictionary($dictionary);
    $patterns = $generator->getPatterns();

    // Convertimos $patterns a array si no lo es ya
    $patternsArray = is_array($patterns) ? $patterns : iterator_to_array($patterns);

    expect($patternsArray)
        ->toBeArray()
        ->and($patternsArray)->each->toBeString()
        ->and($patternsArray)->toHaveCount(3);
});

test('pattern generator creates patterns progressively', function () {
    $words = array_map(fn($i) => "word$i", range(1, 1000));
    $dictionary = LazyDictionary::withWords($words);

    $memoryBefore = memory_get_usage();
    $generator = PatternGenerator::withDictionary($dictionary);
    $patterns = $generator->getPatterns();
    $memoryAfter = memory_get_usage();

    // Verificar que no se carga todo en memoria de golpe
    $memoryDiff = $memoryAfter - $memoryBefore;
    expect($memoryDiff)->toBeLessThan(1024 * 1024); // menos de 1MB
});


test('pattern generator handles multiple words', function () {
    $words = ['test', 'word'];
    $dictionary = LazyDictionary::withWords($words);

    $generator = PatternGenerator::withDictionary($dictionary);
    $patterns = $generator->getPatterns();
    $patternsArray = is_array($patterns) ? $patterns : iterator_to_array($patterns);

    expect($patternsArray)
        ->toBeArray()
        ->and($patternsArray)->each->toBeString()
        ->and($patternsArray)->toHaveCount(2);
});