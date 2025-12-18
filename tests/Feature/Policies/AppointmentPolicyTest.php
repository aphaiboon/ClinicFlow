<?php

use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Organization;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->policy = new AppointmentPolicy;
    $this->admin = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->clinician = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->otherClinician = User::factory()->create([
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
    $this->organization->users()->attach($this->otherClinician->id, [
        'role' => OrganizationRole::Clinician->value,
        'joined_at' => now(),
    ]);
    $this->organization->users()->attach($this->receptionist->id, [
        'role' => OrganizationRole::Receptionist->value,
        'joined_at' => now(),
    ]);
});

it('allows admin to view any appointment', function () {
    $appointment = Appointment::factory()->for($this->organization)->create();

    expect($this->policy->view($this->admin, $appointment))->toBeTrue();
});

it('allows clinician to view their own appointments', function () {
    $appointment = Appointment::factory()->for($this->organization)->create(['user_id' => $this->clinician->id]);

    expect($this->policy->view($this->clinician, $appointment))->toBeTrue();
});

it('prevents clinician from viewing other clinicians appointments', function () {
    $appointment = Appointment::factory()->for($this->organization)->create(['user_id' => $this->otherClinician->id]);

    expect($this->policy->view($this->clinician, $appointment))->toBeFalse();
});

it('allows receptionist to view any appointment', function () {
    $appointment = Appointment::factory()->for($this->organization)->create();

    expect($this->policy->view($this->receptionist, $appointment))->toBeTrue();
});

it('allows admin to create appointments', function () {
    expect($this->policy->create($this->admin))->toBeTrue();
});

it('allows receptionist to create appointments', function () {
    expect($this->policy->create($this->receptionist))->toBeTrue();
});

it('prevents clinician from creating appointments', function () {
    expect($this->policy->create($this->clinician))->toBeFalse();
});

it('allows admin to update any appointment', function () {
    $appointment = Appointment::factory()->for($this->organization)->create();

    expect($this->policy->update($this->admin, $appointment))->toBeTrue();
});

it('allows clinician to update their own appointments', function () {
    $appointment = Appointment::factory()->for($this->organization)->create(['user_id' => $this->clinician->id]);

    expect($this->policy->update($this->clinician, $appointment))->toBeTrue();
});

it('prevents clinician from updating other clinicians appointments', function () {
    $appointment = Appointment::factory()->for($this->organization)->create(['user_id' => $this->otherClinician->id]);

    expect($this->policy->update($this->clinician, $appointment))->toBeFalse();
});

it('allows admin to cancel any appointment', function () {
    $appointment = Appointment::factory()->for($this->organization)->create();

    expect($this->policy->cancel($this->admin, $appointment))->toBeTrue();
});

it('allows receptionist to cancel any appointment', function () {
    $appointment = Appointment::factory()->for($this->organization)->create();

    expect($this->policy->cancel($this->receptionist, $appointment))->toBeTrue();
});

it('prevents clinician from canceling appointments', function () {
    $appointment = Appointment::factory()->for($this->organization)->create();

    expect($this->policy->cancel($this->clinician, $appointment))->toBeFalse();
});

it('allows admin to assign room to any appointment', function () {
    $appointment = Appointment::factory()->for($this->organization)->create();

    expect($this->policy->assignRoom($this->admin, $appointment))->toBeTrue();
});

it('allows receptionist to assign room to any appointment', function () {
    $appointment = Appointment::factory()->for($this->organization)->create();

    expect($this->policy->assignRoom($this->receptionist, $appointment))->toBeTrue();
});

it('prevents clinician from assigning rooms', function () {
    $appointment = Appointment::factory()->for($this->organization)->create();

    expect($this->policy->assignRoom($this->clinician, $appointment))->toBeFalse();
});

it('allows patient to view their own appointments', function () {
    $patient = \App\Models\Patient::factory()->for($this->organization)->create();
    $otherPatient = \App\Models\Patient::factory()->for($this->organization)->create();
    $patientAppointment = Appointment::factory()->for($this->organization)->for($patient)->create();
    $otherPatientAppointment = Appointment::factory()->for($this->organization)->for($otherPatient)->create();

    expect($this->policy->patientView($patient, $patientAppointment))->toBeTrue();
    expect($this->policy->patientView($patient, $otherPatientAppointment))->toBeFalse();
});

it('allows patient to cancel their own cancellable appointments', function () {
    $patient = \App\Models\Patient::factory()->for($this->organization)->create();
    $cancellableAppointment = Appointment::factory()->for($this->organization)->for($patient)->create([
        'status' => \App\Enums\AppointmentStatus::Scheduled,
    ]);

    expect($this->policy->patientCancel($patient, $cancellableAppointment))->toBeTrue();
});

it('prevents patient from canceling non-cancellable appointments', function () {
    $patient = \App\Models\Patient::factory()->for($this->organization)->create();
    $completedAppointment = Appointment::factory()->for($this->organization)->for($patient)->create([
        'status' => \App\Enums\AppointmentStatus::Completed,
    ]);

    expect($this->policy->patientCancel($patient, $completedAppointment))->toBeFalse();
});

it('prevents patient from canceling other patients appointments', function () {
    $patient = \App\Models\Patient::factory()->for($this->organization)->create();
    $otherPatient = \App\Models\Patient::factory()->for($this->organization)->create();
    $otherPatientAppointment = Appointment::factory()->for($this->organization)->for($otherPatient)->create([
        'status' => \App\Enums\AppointmentStatus::Scheduled,
    ]);

    expect($this->policy->patientCancel($patient, $otherPatientAppointment))->toBeFalse();
});

it('staff policies remain unchanged after patient methods added', function () {
    $appointment = Appointment::factory()->for($this->organization)->create();

    expect($this->policy->view($this->admin, $appointment))->toBeTrue();
    expect($this->policy->view($this->receptionist, $appointment))->toBeTrue();
    expect($this->policy->create($this->admin))->toBeTrue();
    expect($this->policy->create($this->receptionist))->toBeTrue();
    expect($this->policy->create($this->clinician))->toBeFalse();
    expect($this->policy->cancel($this->admin, $appointment))->toBeTrue();
    expect($this->policy->cancel($this->receptionist, $appointment))->toBeTrue();
    expect($this->policy->cancel($this->clinician, $appointment))->toBeFalse();
});
