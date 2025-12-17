<?php

use App\Services\Integration\EventIdGenerator;

it('generates event ID with evt_ prefix', function () {
    $generator = new EventIdGenerator();
    $eventId = $generator->generate();

    expect($eventId)
        ->toBeString()
        ->toStartWith('evt_')
        ->toMatch('/^evt_[A-Z0-9]{26}$/');
});

it('generates unique event IDs', function () {
    $generator = new EventIdGenerator();
    
    $ids = [];
    for ($i = 0; $i < 100; $i++) {
        $ids[] = $generator->generate();
    }

    expect(array_unique($ids))->toHaveCount(100);
});

it('generates lexicographically sortable event IDs', function () {
    $generator = new EventIdGenerator();
    
    $ids = [];
    for ($i = 0; $i < 10; $i++) {
        $ids[] = $generator->generate();
        usleep(1000);
    }

    $sortedIds = $ids;
    sort($sortedIds, SORT_STRING);

    expect($ids)->toBe($sortedIds);
});

it('generates event IDs with correct format length', function () {
    $generator = new EventIdGenerator();
    $eventId = $generator->generate();

    expect(strlen($eventId))->toBe(30);
});

