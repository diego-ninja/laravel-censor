<?php

use Ninja\Censor\Collections\MatchCollection;
use Ninja\Censor\Collections\OccurrenceCollection;
use Ninja\Censor\Enums\MatchType;
use Ninja\Censor\Support\Calculator;
use Ninja\Censor\ValueObject\Coincidence;
use Ninja\Censor\ValueObject\Position;

test('collection calculates score correctly', function () {
    $collection = new MatchCollection([
        new Coincidence(
            word: 'fuck',
            type: MatchType::Exact,
            score: Calculator::score(
                'fuck this shit',
                'fuck',
                MatchType::Exact,
                new OccurrenceCollection([new Position(0, 4)])
            ),
            confidence: Calculator::confidence(
                'fuck this shit',
                'fuck',
                MatchType::Exact,
                new OccurrenceCollection([new Position(0, 4)])
            ),
            occurrences: new OccurrenceCollection([new Position(0, 4)])
        ),
        new Coincidence(
            word: 'shit',
            type: MatchType::Pattern,
            score: Calculator::score(
                'fuck this shit',
                'shit',
                MatchType::Pattern,
                new OccurrenceCollection([new Position(9, 4)])
            ),
            confidence: Calculator::confidence(
                'fuck this shit',
                'shit',
                MatchType::Pattern,
                new OccurrenceCollection([new Position(9, 4)])
            ),
            occurrences: new OccurrenceCollection([new Position(9, 4)])
        ),
    ]);

    expect($collection->score()->value())->toBeGreaterThan(0.5);
});

test('collection calculates confidence correctly', function () {
    $collection = new MatchCollection([
        new Coincidence(
            word: 'fuck',
            type: MatchType::Exact,
            score: Calculator::score(
                'fuck this shit',
                'fuck',
                MatchType::Exact,
                new OccurrenceCollection([new Position(0, 4)])
            ),
            confidence: Calculator::confidence(
                'fuck this shit',
                'fuck',
                MatchType::Exact,
                new OccurrenceCollection([new Position(0, 4)])
            ),
            occurrences: new OccurrenceCollection([new Position(0, 4)])
        ),
    ]);

    expect($collection->confidence()->value())->toBeGreaterThanOrEqual(0.85);
});

test('collection determines offensive content correctly', function () {
    $collection = new MatchCollection([
        new Coincidence(
            word: 'fuck',
            type: MatchType::Exact,
            score: Calculator::score(
                'some fuck text',
                'fuck',
                MatchType::Exact,
                new OccurrenceCollection([new Position(5, 4)])
            ),
            confidence: Calculator::confidence(
                'some fuck text',
                'fuck',
                MatchType::Exact,
                new OccurrenceCollection([new Position(5, 4)])
            ),
            occurrences: new OccurrenceCollection([new Position(5, 4)])
        ),
    ]);

    expect($collection->offensive('some fuck text'))->toBeTrue();
});

test('collection cleans text correctly', function () {
    $collection = new MatchCollection([
        new Coincidence(
            word: 'fuck',
            type: MatchType::Exact,
            score: Calculator::score(
                'fuck this shit',
                'fuck',
                MatchType::Exact,
                new OccurrenceCollection([new Position(0, 4)])
            ),
            confidence: Calculator::confidence(
                'fuck this shit',
                'fuck',
                MatchType::Exact,
                new OccurrenceCollection([new Position(0, 4)])
            ),
            occurrences: new OccurrenceCollection([new Position(0, 4)])
        ),
        new Coincidence(
            word: 'shit',
            type: MatchType::Pattern,
            score: Calculator::score(
                'fuck this shit',
                'shit',
                MatchType::Pattern,
                new OccurrenceCollection([new Position(9, 4)])
            ),
            confidence: Calculator::confidence(
                'fuck this shit',
                'shit',
                MatchType::Pattern,
                new OccurrenceCollection([new Position(9, 4)])
            ),
            occurrences: new OccurrenceCollection([new Position(9, 4)])
        ),
    ]);

    $text = 'fuck this shit';
    $cleaned = $collection->clean($text);

    expect($cleaned)->toBe('**** this ****');
});

test('collection handles overlapping matches', function () {
    $collection = new MatchCollection([
        new Coincidence(
            word: 'fuck',
            type: MatchType::Exact,
            score: Calculator::score(
                'fucking hell',
                'fuck',
                MatchType::Exact,
                new OccurrenceCollection([new Position(0, 4)])
            ),
            confidence: Calculator::confidence(
                'fucking hell',
                'fuck',
                MatchType::Exact,
                new OccurrenceCollection([new Position(0, 4)])
            ),
            occurrences: new OccurrenceCollection([new Position(0, 4)])
        ),
        new Coincidence(
            word: 'fucking',
            type: MatchType::Pattern,
            score: Calculator::score(
                'fucking hell',
                'fucking',
                MatchType::Pattern,
                new OccurrenceCollection([new Position(0, 7)])
            ),
            confidence: Calculator::confidence(
                'fucking hell',
                'fucking',
                MatchType::Pattern,
                new OccurrenceCollection([new Position(0, 7)])
            ),
            occurrences: new OccurrenceCollection([new Position(0, 7)])
        ),
    ]);

    $text = 'fucking hell';
    $cleaned = $collection->clean($text);

    expect($cleaned)->toBe('******* hell');
});

test('collection returns correct match count', function () {
    $collection = new MatchCollection([
        new Coincidence(
            word: 'fuck',
            type: MatchType::Exact,
            score: Calculator::score(
                'fuck shit',
                'fuck',
                MatchType::Exact,
                new OccurrenceCollection([new Position(0, 4)])
            ),
            confidence: Calculator::confidence(
                'fuck shit',
                'fuck',
                MatchType::Exact,
                new OccurrenceCollection([new Position(0, 4)])
            ),
            occurrences: new OccurrenceCollection([new Position(0, 4)])
        ),
        new Coincidence(
            word: 'shit',
            type: MatchType::Pattern,
            score: Calculator::score(
                'fuck shit',
                'shit',
                MatchType::Pattern,
                new OccurrenceCollection([new Position(5, 4)])
            ),
            confidence: Calculator::confidence(
                'fuck shit',
                'shit',
                MatchType::Pattern,
                new OccurrenceCollection([new Position(5, 4)])
            ),
            occurrences: new OccurrenceCollection([new Position(5, 4)])
        ),
    ]);

    expect($collection)->toHaveCount(2);
});