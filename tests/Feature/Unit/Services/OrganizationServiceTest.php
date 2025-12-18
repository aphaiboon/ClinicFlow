<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(OrganizationService::class);
    $this->owner = User::factory()->create();
});

it('creates organization with owner', function () {
    $data = [
        'name' => 'Test Clinic',
        'email' => 'test@clinic.com',
        'phone' => '555-1234',
        'address_line_1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701',
        'country' => 'US',
    ];

    $organization = $this->service->create($data, $this->owner);

    expect($organization)->toBeInstanceOf(Organization::class)
        ->and($organization->name)->toBe('Test Clinic')
        ->and($organization->slug)->not->toBeEmpty()
        ->and($organization->users)->toHaveCount(1);

    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $organization->id,
        'user_id' => $this->owner->id,
        'role' => OrganizationRole::Owner->value,
    ]);
});

it('generates unique slug from organization name', function () {
    $data = ['name' => 'Test Clinic'];

    $org1 = $this->service->create($data, $this->owner);
    $org2 = $this->service->create($data, User::factory()->create());

    expect($org1->slug)->not->toBe($org2->slug)
        ->and($org1->slug)->toStartWith(Str::slug('Test Clinic'));
});

it('adds member to organization', function () {
    $organization = Organization::factory()->create();
    $organization->users()->attach($this->owner->id, ['role' => OrganizationRole::Owner->value, 'joined_at' => now()]);

    $newUser = User::factory()->create();

    $this->service->addMember($organization, $newUser, OrganizationRole::Clinician->value);

    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $organization->id,
        'user_id' => $newUser->id,
        'role' => OrganizationRole::Clinician->value,
    ]);
});

it('removes member from organization', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->create();
    $organization->users()->attach($this->owner->id, ['role' => OrganizationRole::Owner->value, 'joined_at' => now()]);
    $organization->users()->attach($member->id, ['role' => OrganizationRole::Clinician->value, 'joined_at' => now()]);

    $this->service->removeMember($organization, $member);

    $this->assertDatabaseMissing('organization_user', [
        'organization_id' => $organization->id,
        'user_id' => $member->id,
    ]);
});

it('prevents removing last owner', function () {
    $organization = Organization::factory()->create();
    $organization->users()->attach($this->owner->id, ['role' => OrganizationRole::Owner->value, 'joined_at' => now()]);

    expect(fn () => $this->service->removeMember($organization, $this->owner))
        ->toThrow(\RuntimeException::class, 'Cannot remove the last owner');
});

it('allows removing owner when other owners exist', function () {
    $organization = Organization::factory()->create();
    $owner2 = User::factory()->create();
    $organization->users()->attach($this->owner->id, ['role' => OrganizationRole::Owner->value, 'joined_at' => now()]);
    $organization->users()->attach($owner2->id, ['role' => OrganizationRole::Owner->value, 'joined_at' => now()]);

    $this->service->removeMember($organization, $this->owner);

    $this->assertDatabaseMissing('organization_user', [
        'organization_id' => $organization->id,
        'user_id' => $this->owner->id,
    ]);
});

it('updates member role', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->create();
    $organization->users()->attach($this->owner->id, ['role' => OrganizationRole::Owner->value, 'joined_at' => now()]);
    $organization->users()->attach($member->id, ['role' => OrganizationRole::Receptionist->value, 'joined_at' => now()]);

    $this->service->updateMemberRole($organization, $member, OrganizationRole::Admin->value);

    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $organization->id,
        'user_id' => $member->id,
        'role' => OrganizationRole::Admin->value,
    ]);
});

it('switches user organization', function () {
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();
    $user = User::factory()->create();
    $org1->users()->attach($user->id, ['role' => OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $org2->users()->attach($user->id, ['role' => OrganizationRole::Clinician->value, 'joined_at' => now()]);

    $this->service->switchUserOrganization($user, $org2);

    expect($user->fresh()->current_organization_id)->toBe($org2->id);
});

it('prevents switching to organization user is not member of', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    expect(fn () => $this->service->switchUserOrganization($user, $organization))
        ->toThrow(\RuntimeException::class, 'User is not a member of this organization');
});
