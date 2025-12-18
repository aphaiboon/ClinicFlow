<?php

use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
});

it('super admin can view user details', function () {
    $user = User::factory()->create(['role' => UserRole::User]);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('super-admin.users.show', $user));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('SuperAdmin/UserDetails')
        ->has('user')
    );
});

it('user details shows user information', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => UserRole::User,
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('super-admin.users.show', $user));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('SuperAdmin/UserDetails')
        ->where('user.name', 'John Doe')
        ->where('user.email', 'john@example.com')
    );
});

it('user details shows organizations user belongs to', function () {
    $user = User::factory()->create(['role' => UserRole::User]);
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    $org1->users()->attach($user->id, ['role' => OrganizationRole::Owner->value, 'joined_at' => now()]);
    $org2->users()->attach($user->id, ['role' => OrganizationRole::Admin->value, 'joined_at' => now()]);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('super-admin.users.show', $user));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('SuperAdmin/UserDetails')
        ->has('user.organizations', 2)
    );
});
