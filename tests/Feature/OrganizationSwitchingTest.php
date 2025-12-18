<?php

use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization1 = Organization::factory()->create(['name' => 'Organization 1']);
    $this->organization2 = Organization::factory()->create(['name' => 'Organization 2']);
    $this->user = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization1->id,
    ]);
    $this->organization1->users()->attach($this->user->id, ['role' => OrganizationRole::Admin->value, 'joined_at' => now()]);
    $this->organization2->users()->attach($this->user->id, ['role' => OrganizationRole::Clinician->value, 'joined_at' => now()]);
});

it('user can switch to organization they belong to', function () {
    $response = $this->actingAs($this->user)
        ->post(route('organizations.switch', $this->organization2));

    $response->assertRedirect(route('dashboard', absolute: false));
    expect($this->user->fresh()->current_organization_id)->toBe($this->organization2->id);
});

it('user cannot switch to organization they do not belong to', function () {
    $organization3 = Organization::factory()->create();

    $response = $this->actingAs($this->user)
        ->post(route('organizations.switch', $organization3));

    $response->assertForbidden();
    expect($this->user->fresh()->current_organization_id)->toBe($this->organization1->id);
});

it('super admin can switch to any organization', function () {
    $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $organization3 = Organization::factory()->create();

    $response = $this->actingAs($superAdmin)
        ->post(route('organizations.switch', $organization3));

    $response->assertRedirect(route('dashboard', absolute: false));
    expect($superAdmin->fresh()->current_organization_id)->toBe($organization3->id);
});

it('returns list of user organizations', function () {
    $response = $this->actingAs($this->user)
        ->get(route('organizations.index'));

    $response->assertOk();
    $response->assertJsonCount(2);
    $response->assertJsonFragment(['id' => $this->organization1->id]);
    $response->assertJsonFragment(['id' => $this->organization2->id]);
});
