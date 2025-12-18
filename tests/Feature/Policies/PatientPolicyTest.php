<?php

use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Policies\PatientPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->policy = new PatientPolicy;
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

it('allows admin to view any patient', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    expect($this->policy->view($this->admin, $patient))->toBeTrue();
});

it('allows clinician to view any patient', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    expect($this->policy->view($this->clinician, $patient))->toBeTrue();
});

it('allows receptionist to view any patient', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    expect($this->policy->view($this->receptionist, $patient))->toBeTrue();
});

it('allows admin to create patients', function () {
    expect($this->policy->create($this->admin))->toBeTrue();
});

it('allows receptionist to create patients', function () {
    expect($this->policy->create($this->receptionist))->toBeTrue();
});

it('prevents clinician from creating patients', function () {
    expect($this->policy->create($this->clinician))->toBeFalse();
});

it('allows admin to update any patient', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    expect($this->policy->update($this->admin, $patient))->toBeTrue();
});

it('allows receptionist to update any patient', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    expect($this->policy->update($this->receptionist, $patient))->toBeTrue();
});

it('prevents clinician from updating patients', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    expect($this->policy->update($this->clinician, $patient))->toBeFalse();
});

it('allows admin to delete any patient', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    expect($this->policy->delete($this->admin, $patient))->toBeTrue();
});

it('prevents clinician from deleting patients', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    expect($this->policy->delete($this->clinician, $patient))->toBeFalse();
});

it('prevents receptionist from deleting patients', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    expect($this->policy->delete($this->receptionist, $patient))->toBeFalse();
});

it('allows patient to view their own profile', function () {
    $patient = Patient::factory()->for($this->organization)->create();
    $otherPatient = Patient::factory()->for($this->organization)->create();

    expect($this->policy->patientView($patient, $patient))->toBeTrue();
    expect($this->policy->patientView($patient, $otherPatient))->toBeFalse();
});

it('allows patient to update their own profile', function () {
    $patient = Patient::factory()->for($this->organization)->create();
    $otherPatient = Patient::factory()->for($this->organization)->create();

    expect($this->policy->patientUpdate($patient, $patient))->toBeTrue();
    expect($this->policy->patientUpdate($patient, $otherPatient))->toBeFalse();
});

it('staff policies remain unchanged after patient methods added', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    expect($this->policy->view($this->admin, $patient))->toBeTrue();
    expect($this->policy->view($this->clinician, $patient))->toBeTrue();
    expect($this->policy->view($this->receptionist, $patient))->toBeTrue();
    expect($this->policy->create($this->admin))->toBeTrue();
    expect($this->policy->create($this->receptionist))->toBeTrue();
    expect($this->policy->create($this->clinician))->toBeFalse();
});
