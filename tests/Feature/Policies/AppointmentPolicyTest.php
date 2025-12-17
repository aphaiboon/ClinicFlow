<?php

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new AppointmentPolicy;
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->clinician = User::factory()->create(['role' => UserRole::Clinician]);
    $this->otherClinician = User::factory()->create(['role' => UserRole::Clinician]);
    $this->receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
});

it('allows admin to view any appointment', function () {
    $appointment = Appointment::factory()->create();

    expect($this->policy->view($this->admin, $appointment))->toBeTrue();
});

it('allows clinician to view their own appointments', function () {
    $appointment = Appointment::factory()->create(['user_id' => $this->clinician->id]);

    expect($this->policy->view($this->clinician, $appointment))->toBeTrue();
});

it('prevents clinician from viewing other clinicians appointments', function () {
    $appointment = Appointment::factory()->create(['user_id' => $this->otherClinician->id]);

    expect($this->policy->view($this->clinician, $appointment))->toBeFalse();
});

it('allows receptionist to view any appointment', function () {
    $appointment = Appointment::factory()->create();

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
    $appointment = Appointment::factory()->create();

    expect($this->policy->update($this->admin, $appointment))->toBeTrue();
});

it('allows clinician to update their own appointments', function () {
    $appointment = Appointment::factory()->create(['user_id' => $this->clinician->id]);

    expect($this->policy->update($this->clinician, $appointment))->toBeTrue();
});

it('prevents clinician from updating other clinicians appointments', function () {
    $appointment = Appointment::factory()->create(['user_id' => $this->otherClinician->id]);

    expect($this->policy->update($this->clinician, $appointment))->toBeFalse();
});

it('allows admin to cancel any appointment', function () {
    $appointment = Appointment::factory()->create();

    expect($this->policy->cancel($this->admin, $appointment))->toBeTrue();
});

it('allows receptionist to cancel any appointment', function () {
    $appointment = Appointment::factory()->create();

    expect($this->policy->cancel($this->receptionist, $appointment))->toBeTrue();
});

it('prevents clinician from canceling appointments', function () {
    $appointment = Appointment::factory()->create();

    expect($this->policy->cancel($this->clinician, $appointment))->toBeFalse();
});

it('allows admin to assign room to any appointment', function () {
    $appointment = Appointment::factory()->create();

    expect($this->policy->assignRoom($this->admin, $appointment))->toBeTrue();
});

it('allows receptionist to assign room to any appointment', function () {
    $appointment = Appointment::factory()->create();

    expect($this->policy->assignRoom($this->receptionist, $appointment))->toBeTrue();
});

it('prevents clinician from assigning rooms', function () {
    $appointment = Appointment::factory()->create();

    expect($this->policy->assignRoom($this->clinician, $appointment))->toBeFalse();
});
