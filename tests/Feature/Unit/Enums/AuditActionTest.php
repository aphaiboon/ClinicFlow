<?php

use App\Enums\AuditAction;

it('has all required values', function () {
    expect(AuditAction::cases())->toHaveCount(6)
        ->and(AuditAction::Create)->toBeInstanceOf(AuditAction::class)
        ->and(AuditAction::Read)->toBeInstanceOf(AuditAction::class)
        ->and(AuditAction::Update)->toBeInstanceOf(AuditAction::class)
        ->and(AuditAction::Delete)->toBeInstanceOf(AuditAction::class)
        ->and(AuditAction::Login)->toBeInstanceOf(AuditAction::class)
        ->and(AuditAction::Logout)->toBeInstanceOf(AuditAction::class);
});

it('has correct string values', function () {
    expect(AuditAction::Create->value)->toBe('create')
        ->and(AuditAction::Read->value)->toBe('read')
        ->and(AuditAction::Update->value)->toBe('update')
        ->and(AuditAction::Delete->value)->toBe('delete')
        ->and(AuditAction::Login->value)->toBe('login')
        ->and(AuditAction::Logout->value)->toBe('logout');
});

it('can be created from value', function (string $value, AuditAction $expected) {
    expect(AuditAction::from($value))->toBe($expected);
})->with([
    ['create', AuditAction::Create],
    ['read', AuditAction::Read],
    ['update', AuditAction::Update],
    ['delete', AuditAction::Delete],
    ['login', AuditAction::Login],
    ['logout', AuditAction::Logout],
]);

it('throws exception for invalid value', function () {
    expect(fn () => AuditAction::from('invalid'))
        ->toThrow(ValueError::class);
});
