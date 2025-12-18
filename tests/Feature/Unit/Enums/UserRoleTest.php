<?php

use App\Enums\UserRole;

it('has super admin role', function () {
    expect(UserRole::SuperAdmin->value)->toBe('super_admin');
    expect(UserRole::SuperAdmin->isSuperAdmin())->toBeTrue();
});

it('has user role', function () {
    expect(UserRole::User->value)->toBe('user');
    expect(UserRole::User->isSuperAdmin())->toBeFalse();
});

it('can be created from value', function (string $value, UserRole $expected) {
    expect(UserRole::from($value))->toBe($expected);
})->with([
    ['super_admin', UserRole::SuperAdmin],
    ['user', UserRole::User],
]);
