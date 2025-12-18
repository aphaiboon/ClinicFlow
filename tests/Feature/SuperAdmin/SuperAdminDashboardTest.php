<?php

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
});

it('super admin can view dashboard', function () {
    $response = $this->actingAs($this->superAdmin)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('role', 'super_admin')
    );
});

it('super admin dashboard shows organization count', function () {
    Organization::factory()->count(5)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('stats.organizationCount')
        ->where('stats.organizationCount', 5)
    );
});

it('super admin dashboard shows user count', function () {
    User::factory()->count(10)->create(['role' => UserRole::User]);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('stats.userCount')
        ->where('stats.userCount', 10)
    );
});
