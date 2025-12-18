<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->owner = User::factory()->create(['current_organization_id' => $this->organization->id]);
    $this->admin = User::factory()->create(['current_organization_id' => $this->organization->id]);
    $this->clinician = User::factory()->create(['current_organization_id' => $this->organization->id]);
    $this->receptionist = User::factory()->create(['current_organization_id' => $this->organization->id]);
    $this->otherUser = User::factory()->create();

    $this->organization->users()->attach($this->owner->id, ['role' => OrganizationRole::Owner->value, 'joined_at' => now()]);
    $this->organization->users()->attach($this->admin->id, ['role' => OrganizationRole::Admin->value, 'joined_at' => now()]);
    $this->organization->users()->attach($this->clinician->id, ['role' => OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $this->organization->users()->attach($this->receptionist->id, ['role' => OrganizationRole::Receptionist->value, 'joined_at' => now()]);
});

it('allows owner to view organization', function () {
    expect($this->owner->can('view', $this->organization))->toBeTrue();
});

it('allows admin to view organization', function () {
    expect($this->admin->can('view', $this->organization))->toBeTrue();
});

it('allows members to view organization', function () {
    expect($this->clinician->can('view', $this->organization))->toBeTrue();
    expect($this->receptionist->can('view', $this->organization))->toBeTrue();
});

it('prevents non-members from viewing organization', function () {
    expect($this->otherUser->can('view', $this->organization))->toBeFalse();
});

it('allows owner to update organization', function () {
    expect($this->owner->can('update', $this->organization))->toBeTrue();
});

it('allows admin to update organization', function () {
    expect($this->admin->can('update', $this->organization))->toBeTrue();
});

it('prevents clinician from updating organization', function () {
    expect($this->clinician->can('update', $this->organization))->toBeFalse();
});

it('prevents receptionist from updating organization', function () {
    expect($this->receptionist->can('update', $this->organization))->toBeFalse();
});

it('allows owner to delete organization', function () {
    expect($this->owner->can('delete', $this->organization))->toBeTrue();
});

it('prevents admin from deleting organization', function () {
    expect($this->admin->can('delete', $this->organization))->toBeFalse();
});

it('prevents non-owner from deleting organization', function () {
    expect($this->clinician->can('delete', $this->organization))->toBeFalse();
    expect($this->receptionist->can('delete', $this->organization))->toBeFalse();
});

it('allows owner to manage members', function () {
    expect($this->owner->can('manageMembers', $this->organization))->toBeTrue();
});

it('allows admin to manage members', function () {
    expect($this->admin->can('manageMembers', $this->organization))->toBeTrue();
});

it('prevents clinician from managing members', function () {
    expect($this->clinician->can('manageMembers', $this->organization))->toBeFalse();
});
