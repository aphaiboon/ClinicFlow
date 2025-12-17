<?php

use App\Enums\UserRole;
use App\Models\User;

it('has role attribute', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    expect($user->role)->toBe(UserRole::Admin);
});

it('casts role to enum', function () {
    $user = User::factory()->create(['role' => UserRole::Clinician]);

    expect($user->role)->toBeInstanceOf(UserRole::class)
        ->and($user->getAttributes()['role'])->toBe('clinician');
});

it('has default role of receptionist', function () {
    $user = new User;
    $user->name = 'Test User';
    $user->email = 'test@example.com';
    $user->password = bcrypt('password');
    $user->save();

    expect($user->fresh()->role)->toBe(UserRole::Receptionist);
});

it('can check if user is admin', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $clinician = User::factory()->create(['role' => UserRole::Clinician]);
    $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);

    expect($admin->role->isAdmin())->toBeTrue()
        ->and($clinician->role->isAdmin())->toBeFalse()
        ->and($receptionist->role->isAdmin())->toBeFalse();
});

it('can scope users by role', function () {
    User::factory()->create(['role' => UserRole::Admin]);
    User::factory()->create(['role' => UserRole::Clinician]);
    User::factory()->create(['role' => UserRole::Receptionist]);

    $admins = User::where('role', UserRole::Admin->value)->get();

    expect($admins)->toHaveCount(1)
        ->and($admins->first()->role)->toBe(UserRole::Admin);
});
