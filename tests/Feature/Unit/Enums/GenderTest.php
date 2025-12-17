<?php

use App\Enums\Gender;

it('has all required values', function () {
    expect(Gender::cases())->toHaveCount(4)
        ->and(Gender::Male)->toBeInstanceOf(Gender::class)
        ->and(Gender::Female)->toBeInstanceOf(Gender::class)
        ->and(Gender::Other)->toBeInstanceOf(Gender::class)
        ->and(Gender::PreferNotToSay)->toBeInstanceOf(Gender::class);
});

it('has correct string values', function () {
    expect(Gender::Male->value)->toBe('male')
        ->and(Gender::Female->value)->toBe('female')
        ->and(Gender::Other->value)->toBe('other')
        ->and(Gender::PreferNotToSay->value)->toBe('prefer_not_to_say');
});

it('can be created from value', function (string $value, Gender $expected) {
    expect(Gender::from($value))->toBe($expected);
})->with([
    ['male', Gender::Male],
    ['female', Gender::Female],
    ['other', Gender::Other],
    ['prefer_not_to_say', Gender::PreferNotToSay],
]);

it('throws exception for invalid value', function () {
    expect(fn () => Gender::from('invalid'))
        ->toThrow(ValueError::class);
});

it('can get all values as array', function () {
    $values = array_column(Gender::cases(), 'value');

    expect($values)->toContain('male', 'female', 'other', 'prefer_not_to_say');
});
