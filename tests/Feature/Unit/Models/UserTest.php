<?php

use App\Enums\UserRole;
use App\Models\User;

it('has role attribute', function () {
    $user = User::factory()->create(['role' => UserRole::User]);

    expect($user->role)->toBe(UserRole::User);
});

it('casts role to enum', function () {
    $user = User::factory()->create(['role' => UserRole::User]);

    expect($user->role)->toBeInstanceOf(UserRole::class)
        ->and($user->getAttributes()['role'])->toBe('user');
});

it('has default role of user', function () {
    $user = new User;
    $user->name = 'Test User';
    $user->email = 'test@example.com';
    $user->password = bcrypt('password');
    $user->save();

    expect($user->fresh()->role)->toBe(UserRole::User);
});

it('can check if user is super admin', function () {
    $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $user = User::factory()->create(['role' => UserRole::User]);

    expect($superAdmin->isSuperAdmin())->toBeTrue()
        ->and($user->isSuperAdmin())->toBeFalse();
});

it('can scope users by role', function () {
    User::factory()->create(['role' => UserRole::SuperAdmin]);
    User::factory()->create(['role' => UserRole::User]);
    User::factory()->create(['role' => UserRole::User]);

    $users = User::where('role', UserRole::User->value)->get();

    expect($users)->toHaveCount(2)
        ->and($users->first()->role)->toBe(UserRole::User);
});
