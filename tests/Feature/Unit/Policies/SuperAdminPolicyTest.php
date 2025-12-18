<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->regularUser = User::factory()->create(['role' => UserRole::User]);
});

it('allows super admin to view any organizations', function () {
    $policy = new \App\Policies\SuperAdminPolicy;
    expect($policy->viewAnyOrganizations($this->superAdmin))->toBeTrue();
});

it('prevents regular user from viewing any organizations', function () {
    $policy = new \App\Policies\SuperAdminPolicy;
    expect($policy->viewAnyOrganizations($this->regularUser))->toBeFalse();
});

it('allows super admin to view any users', function () {
    $policy = new \App\Policies\SuperAdminPolicy;
    expect($policy->viewAnyUsers($this->superAdmin))->toBeTrue();
});

it('prevents regular user from viewing any users', function () {
    $policy = new \App\Policies\SuperAdminPolicy;
    expect($policy->viewAnyUsers($this->regularUser))->toBeFalse();
});

it('allows super admin to manage organizations', function () {
    $policy = new \App\Policies\SuperAdminPolicy;
    expect($policy->manageOrganizations($this->superAdmin))->toBeTrue();
});

it('prevents regular user from managing organizations', function () {
    $policy = new \App\Policies\SuperAdminPolicy;
    expect($policy->manageOrganizations($this->regularUser))->toBeFalse();
});
