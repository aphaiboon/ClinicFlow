<?php

use App\Enums\UserRole;

it('has all required values', function () {
    expect(UserRole::cases())->toHaveCount(3)
        ->and(UserRole::Admin)->toBeInstanceOf(UserRole::class)
        ->and(UserRole::Clinician)->toBeInstanceOf(UserRole::class)
        ->and(UserRole::Receptionist)->toBeInstanceOf(UserRole::class);
});

it('has correct string values', function () {
    expect(UserRole::Admin->value)->toBe('admin')
        ->and(UserRole::Clinician->value)->toBe('clinician')
        ->and(UserRole::Receptionist->value)->toBe('receptionist');
});

it('can be created from value', function (string $value, UserRole $expected) {
    expect(UserRole::from($value))->toBe($expected);
})->with([
    ['admin', UserRole::Admin],
    ['clinician', UserRole::Clinician],
    ['receptionist', UserRole::Receptionist],
]);

it('throws exception for invalid value', function () {
    expect(fn () => UserRole::from('invalid'))
        ->toThrow(ValueError::class);
});

it('can check if role is admin', function () {
    expect(UserRole::Admin->isAdmin())->toBeTrue()
        ->and(UserRole::Clinician->isAdmin())->toBeFalse()
        ->and(UserRole::Receptionist->isAdmin())->toBeFalse();
});
