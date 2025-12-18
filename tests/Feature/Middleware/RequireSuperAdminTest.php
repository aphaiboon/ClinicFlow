<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->regularUser = User::factory()->create(['role' => UserRole::User]);
});

it('allows super admin to access super admin routes', function () {
    $response = $this->actingAs($this->superAdmin)
        ->get(route('super-admin.dashboard'));

    $response->assertStatus(200);
});

it('denies regular user access to super admin routes', function () {
    $response = $this->actingAs($this->regularUser)
        ->get(route('super-admin.dashboard'));

    $response->assertForbidden();
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get(route('super-admin.dashboard'));

    $response->assertRedirect(route('login'));
});
