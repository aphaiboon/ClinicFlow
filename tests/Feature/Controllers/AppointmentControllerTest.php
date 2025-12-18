<?php

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = \App\Models\Organization::factory()->create();
    $this->admin = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->receptionist = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->clinician = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->admin->id, ['role' => \App\Enums\OrganizationRole::Admin->value, 'joined_at' => now()]);
    $this->organization->users()->attach($this->receptionist->id, ['role' => \App\Enums\OrganizationRole::Receptionist->value, 'joined_at' => now()]);
    $this->organization->users()->attach($this->clinician->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $this->patient = Patient::factory()->for($this->organization)->create();
    $this->examRoom = ExamRoom::factory()->for($this->organization)->create();
});

it('requires authentication to view appointments index', function () {
    $response = $this->get('/appointments');

    $response->assertRedirect(route('login'));
});

it('displays appointments index for authenticated users', function () {
    Appointment::factory()->for($this->organization)->count(5)->create();

    $response = $this->actingAs($this->receptionist)->get('/appointments');

    $response->assertSuccessful();
});

it('allows admin to view appointments index', function () {
    Appointment::factory()->for($this->organization)->count(3)->create();

    $response = $this->actingAs($this->admin)->get('/appointments');

    $response->assertSuccessful();
});

it('allows receptionist to view appointments index', function () {
    Appointment::factory()->for($this->organization)->count(3)->create();

    $response = $this->actingAs($this->receptionist)->get('/appointments');

    $response->assertSuccessful();
});

it('allows clinician to view appointments index', function () {
    Appointment::factory()->for($this->organization)->count(3)->create();

    $response = $this->actingAs($this->clinician)->get('/appointments');

    $response->assertSuccessful();
});

it('displays create appointment form for authorized users', function () {
    $response = $this->actingAs($this->receptionist)->get('/appointments/create');

    $response->assertSuccessful();
});

it('prevents clinician from accessing create appointment form', function () {
    $response = $this->actingAs($this->clinician)->get('/appointments/create');

    $response->assertForbidden();
});

it('allows receptionist to create an appointment', function () {
    $appointmentData = [
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => now()->addDay()->toDateString(),
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
        'appointment_type' => AppointmentType::Routine->value,
    ];

    $response = $this->actingAs($this->receptionist)->post('/appointments', $appointmentData);

    $response->assertRedirect();
    $this->assertDatabaseHas('appointments', [
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'status' => AppointmentStatus::Scheduled->value,
    ]);
});

it('prevents clinician from creating an appointment', function () {
    $appointmentData = [
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => now()->addDay()->toDateString(),
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
        'appointment_type' => AppointmentType::Routine->value,
    ];

    $response = $this->actingAs($this->clinician)->post('/appointments', $appointmentData);

    $response->assertForbidden();
});

it('validates required fields when creating appointment', function () {
    $response = $this->actingAs($this->receptionist)->post('/appointments', []);

    $response->assertSessionHasErrors(['patient_id', 'user_id', 'appointment_date', 'appointment_time', 'duration_minutes', 'appointment_type']);
});

it('displays appointment details', function () {
    $appointment = Appointment::factory()->for($this->organization)->create();

    $response = $this->actingAs($this->receptionist)->get("/appointments/{$appointment->id}");

    $response->assertSuccessful();
});

it('displays edit appointment form for authorized users', function () {
    $appointment = Appointment::factory()->for($this->organization)->create(['user_id' => $this->clinician->id]);

    $response = $this->actingAs($this->clinician)->get("/appointments/{$appointment->id}/edit");

    $response->assertSuccessful();
});

it('allows clinician to update their own appointment', function () {
    $appointment = Appointment::factory()->for($this->organization)->create(['user_id' => $this->clinician->id]);

    $response = $this->actingAs($this->clinician)
        ->put("/appointments/{$appointment->id}", ['notes' => 'Updated notes']);

    $response->assertRedirect();
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'notes' => 'Updated notes']);
});

it('prevents clinician from updating other clinicians appointments', function () {
    $otherClinician = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($otherClinician->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $appointment = Appointment::factory()->for($this->organization)->create(['user_id' => $otherClinician->id]);

    $response = $this->actingAs($this->clinician)
        ->put("/appointments/{$appointment->id}", ['notes' => 'Updated notes']);

    $response->assertForbidden();
});

it('allows receptionist to cancel an appointment', function () {
    $appointment = Appointment::factory()->for($this->organization)->create(['status' => AppointmentStatus::Scheduled]);

    $response = $this->actingAs($this->receptionist)
        ->post("/appointments/{$appointment->id}/cancel", ['reason' => 'Patient request']);

    $response->assertRedirect();
    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => AppointmentStatus::Cancelled->value,
        'cancellation_reason' => 'Patient request',
    ]);
});

it('prevents clinician from canceling appointments', function () {
    $appointment = Appointment::factory()->for($this->organization)->create(['user_id' => $this->clinician->id]);

    $response = $this->actingAs($this->clinician)
        ->post("/appointments/{$appointment->id}/cancel", ['reason' => 'Patient request']);

    $response->assertForbidden();
});

it('allows receptionist to assign room to appointment', function () {
    $appointment = Appointment::factory()->for($this->organization)->create(['exam_room_id' => null]);

    $response = $this->actingAs($this->receptionist)
        ->post("/appointments/{$appointment->id}/assign-room", ['exam_room_id' => $this->examRoom->id]);

    $response->assertRedirect();
    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'exam_room_id' => $this->examRoom->id,
    ]);
});

it('prevents clinician from assigning room', function () {
    $appointment = Appointment::factory()->for($this->organization)->create(['user_id' => $this->clinician->id]);

    $response = $this->actingAs($this->clinician)
        ->post("/appointments/{$appointment->id}/assign-room", ['exam_room_id' => $this->examRoom->id]);

    $response->assertForbidden();
});

it('validates room assignment request', function () {
    $appointment = Appointment::factory()->for($this->organization)->create();

    $response = $this->actingAs($this->receptionist)
        ->post("/appointments/{$appointment->id}/assign-room", []);

    $response->assertSessionHasErrors(['exam_room_id']);
});
