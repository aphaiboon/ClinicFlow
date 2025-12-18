<?php

use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->organization = Organization::factory()->create();
});

it('super admin can view organization data', function () {
    $response = $this->actingAs($this->superAdmin)
        ->get(route('super-admin.organizations.show', $this->organization));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('SuperAdmin/OrganizationDataView')
        ->has('organization')
    );
});

it('organization data view shows organization details', function () {
    $organization = Organization::factory()->create([
        'name' => 'Test Clinic',
        'email' => 'test@clinic.com',
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('super-admin.organizations.show', $organization));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('SuperAdmin/OrganizationDataView')
        ->where('organization.name', 'Test Clinic')
        ->where('organization.email', 'test@clinic.com')
    );
});

it('organization data view shows members', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $this->organization->users()->attach($user1->id, ['role' => OrganizationRole::Owner->value, 'joined_at' => now()]);
    $this->organization->users()->attach($user2->id, ['role' => OrganizationRole::Admin->value, 'joined_at' => now()]);

    $response = $this->actingAs($this->superAdmin)
        ->get(route('super-admin.organizations.show', $this->organization));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('SuperAdmin/OrganizationDataView')
        ->has('organization.users', 2)
    );
});

it('organization data view shows patients count', function () {
    Patient::factory()->for($this->organization)->count(5)->create();

    $response = $this->actingAs($this->superAdmin)
        ->get(route('super-admin.organizations.show', $this->organization));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('SuperAdmin/OrganizationDataView')
        ->has('stats.patientsCount')
        ->where('stats.patientsCount', 5)
    );
});
