<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->regularUser = User::factory()->create(['role' => UserRole::User]);
});

it('allows super admin to view any organization', function () {
    expect($this->superAdmin->can('viewAny', \App\Models\Organization::class))->toBeTrue();
});

it('prevents regular user from viewing any organization', function () {
    expect($this->regularUser->can('viewAny', \App\Models\Organization::class))->toBeFalse();
});

it('allows super admin to view any user', function () {
    expect($this->superAdmin->can('viewAny', User::class))->toBeTrue();
});

it('prevents regular user from viewing any user', function () {
    expect($this->regularUser->can('viewAny', User::class))->toBeFalse();
});

it('allows super admin to manage organizations', function () {
    expect($this->superAdmin->can('manage', \App\Models\Organization::class))->toBeTrue();
});

it('prevents regular user from managing organizations', function () {
    expect($this->regularUser->can('manage', \App\Models\Organization::class))->toBeFalse();
});
