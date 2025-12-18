<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
});

it('super admin can view users list', function () {
    User::factory()->count(5)->create(['role' => UserRole::User]);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('super-admin.users.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('SuperAdmin/UsersList')
        ->has('users.data', 5)
    );
});

it('users list shows user details', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => UserRole::User,
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('super-admin.users.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('SuperAdmin/UsersList')
        ->has('users.data.0', fn (Assert $userData) => $userData
            ->where('name', 'John Doe')
            ->where('email', 'john@example.com')
        )
    );
});
