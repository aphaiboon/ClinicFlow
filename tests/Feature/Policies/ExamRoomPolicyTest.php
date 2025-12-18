<?php

use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\ExamRoom;
use App\Models\Organization;
use App\Models\User;
use App\Policies\ExamRoomPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->policy = new ExamRoomPolicy;
    $this->admin = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->clinician = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->receptionist = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->organization->users()->attach($this->admin->id, [
        'role' => OrganizationRole::Admin->value,
        'joined_at' => now(),
    ]);
    $this->organization->users()->attach($this->clinician->id, [
        'role' => OrganizationRole::Clinician->value,
        'joined_at' => now(),
    ]);
    $this->organization->users()->attach($this->receptionist->id, [
        'role' => OrganizationRole::Receptionist->value,
        'joined_at' => now(),
    ]);
});

it('allows admin to view any room', function () {
    $room = ExamRoom::factory()->for($this->organization)->create();

    expect($this->policy->view($this->admin, $room))->toBeTrue();
});

it('allows clinician to view any room', function () {
    $room = ExamRoom::factory()->for($this->organization)->create();

    expect($this->policy->view($this->clinician, $room))->toBeTrue();
});

it('allows receptionist to view any room', function () {
    $room = ExamRoom::factory()->for($this->organization)->create();

    expect($this->policy->view($this->receptionist, $room))->toBeTrue();
});

it('allows admin to create rooms', function () {
    expect($this->policy->create($this->admin))->toBeTrue();
});

it('prevents clinician from creating rooms', function () {
    expect($this->policy->create($this->clinician))->toBeFalse();
});

it('prevents receptionist from creating rooms', function () {
    expect($this->policy->create($this->receptionist))->toBeFalse();
});

it('allows admin to update any room', function () {
    $room = ExamRoom::factory()->for($this->organization)->create();

    expect($this->policy->update($this->admin, $room))->toBeTrue();
});

it('prevents clinician from updating rooms', function () {
    $room = ExamRoom::factory()->for($this->organization)->create();

    expect($this->policy->update($this->clinician, $room))->toBeFalse();
});

it('prevents receptionist from updating rooms', function () {
    $room = ExamRoom::factory()->for($this->organization)->create();

    expect($this->policy->update($this->receptionist, $room))->toBeFalse();
});

it('allows admin to activate any room', function () {
    $room = ExamRoom::factory()->for($this->organization)->inactive()->create();

    expect($this->policy->activate($this->admin, $room))->toBeTrue();
});

it('allows admin to deactivate any room', function () {
    $room = ExamRoom::factory()->for($this->organization)->create();

    expect($this->policy->deactivate($this->admin, $room))->toBeTrue();
});

it('prevents clinician from activating rooms', function () {
    $room = ExamRoom::factory()->for($this->organization)->inactive()->create();

    expect($this->policy->activate($this->clinician, $room))->toBeFalse();
});

it('prevents receptionist from deactivating rooms', function () {
    $room = ExamRoom::factory()->for($this->organization)->create();

    expect($this->policy->deactivate($this->receptionist, $room))->toBeFalse();
});
