<?php

use App\Services\TimeParser;
use Carbon\Carbon;

test('parses time string in H:i format', function () {
    $time = TimeParser::parse('10:30');

    expect($time)->toBeInstanceOf(Carbon::class)
        ->and($time->hour)->toBe(10)
        ->and($time->minute)->toBe(30)
        ->and($time->second)->toBe(0);
});

test('parses time string in H:i:s format', function () {
    $time = TimeParser::parse('14:45:30');

    expect($time)->toBeInstanceOf(Carbon::class)
        ->and($time->hour)->toBe(14)
        ->and($time->minute)->toBe(45)
        ->and($time->second)->toBe(30);
});

test('handles time string with only hours', function () {
    $time = TimeParser::parse('9');

    expect($time)->toBeInstanceOf(Carbon::class)
        ->and($time->hour)->toBe(9)
        ->and($time->minute)->toBe(0)
        ->and($time->second)->toBe(0);
});

test('defaults seconds to zero when not provided', function () {
    $time = TimeParser::parse('12:30');

    expect($time->second)->toBe(0);
});

test('handles midnight correctly', function () {
    $time = TimeParser::parse('00:00');

    expect($time->hour)->toBe(0)
        ->and($time->minute)->toBe(0);
});

test('handles end of day correctly', function () {
    $time = TimeParser::parse('23:59');

    expect($time->hour)->toBe(23)
        ->and($time->minute)->toBe(59);
});
