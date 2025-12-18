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

it('super admin can view organizations list', function () {
    Organization::factory()->count(3)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('super-admin.organizations.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('SuperAdmin/OrganizationsList')
        ->has('organizations.data', 3)
    );
});

it('organizations list shows organization details', function () {
    $organization = Organization::factory()->create([
        'name' => 'Test Clinic',
        'email' => 'test@clinic.com',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('super-admin.organizations.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('SuperAdmin/OrganizationsList')
        ->has('organizations.data.0', fn (Assert $org) => $org
            ->where('name', 'Test Clinic')
            ->where('email', 'test@clinic.com')
            ->where('is_active', true)
        )
    );
});
