<?php

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ExamRoom;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('enforces authorization for different user roles accessing patients', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
    $clinician = User::factory()->create(['role' => UserRole::Clinician]);
    $patient = Patient::factory()->create();

    $this->actingAs($admin)->get('/patients')->assertSuccessful();
    $this->actingAs($receptionist)->get('/patients')->assertSuccessful();
    $this->actingAs($clinician)->get('/patients')->assertSuccessful();

    $this->actingAs($admin)->get("/patients/{$patient->id}")->assertSuccessful();
    $this->actingAs($receptionist)->get("/patients/{$patient->id}")->assertSuccessful();
    $this->actingAs($clinician)->get("/patients/{$patient->id}")->assertSuccessful();

    $this->actingAs($admin)->get('/patients/create')->assertSuccessful();
    $this->actingAs($receptionist)->get('/patients/create')->assertSuccessful();
    $this->actingAs($clinician)->get('/patients/create')->assertForbidden();
});

it('enforces authorization for different user roles accessing appointments', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
    $clinician = User::factory()->create(['role' => UserRole::Clinician]);
    $appointment = Appointment::factory()->create(['user_id' => $clinician->id]);

    $this->actingAs($admin)->get('/appointments')->assertSuccessful();
    $this->actingAs($receptionist)->get('/appointments')->assertSuccessful();
    $this->actingAs($clinician)->get('/appointments')->assertSuccessful();

    $this->actingAs($admin)->get("/appointments/{$appointment->id}")->assertSuccessful();
    $this->actingAs($receptionist)->get("/appointments/{$appointment->id}")->assertSuccessful();
    $this->actingAs($clinician)->get("/appointments/{$appointment->id}")->assertSuccessful();

    $this->actingAs($admin)->get('/appointments/create')->assertSuccessful();
    $this->actingAs($receptionist)->get('/appointments/create')->assertSuccessful();
    $this->actingAs($clinician)->get('/appointments/create')->assertForbidden();
});

it('enforces authorization for appointment cancellation by role', function () {
    $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
    $clinician = User::factory()->create(['role' => UserRole::Clinician]);
    $appointment = Appointment::factory()->create(['status' => \App\Enums\AppointmentStatus::Scheduled]);

    $this->actingAs($receptionist)
        ->post("/appointments/{$appointment->id}/cancel", ['reason' => 'Test'])
        ->assertRedirect();

    $appointment2 = Appointment::factory()->create(['status' => \App\Enums\AppointmentStatus::Scheduled]);

    $this->actingAs($clinician)
        ->post("/appointments/{$appointment2->id}/cancel", ['reason' => 'Test'])
        ->assertForbidden();
});

it('enforces admin-only access to audit logs', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
    $clinician = User::factory()->create(['role' => UserRole::Clinician]);
    $auditLog = AuditLog::factory()->create();

    $this->actingAs($admin)->get('/audit-logs')->assertSuccessful();
    $this->actingAs($receptionist)->get('/audit-logs')->assertForbidden();
    $this->actingAs($clinician)->get('/audit-logs')->assertForbidden();

    $this->actingAs($admin)->get("/audit-logs/{$auditLog->id}")->assertSuccessful();
    $this->actingAs($receptionist)->get("/audit-logs/{$auditLog->id}")->assertForbidden();
    $this->actingAs($clinician)->get("/audit-logs/{$auditLog->id}")->assertForbidden();
});

it('enforces authorization for exam room management', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
    $clinician = User::factory()->create(['role' => UserRole::Clinician]);
    $room = ExamRoom::factory()->create();

    $this->actingAs($admin)->get('/exam-rooms')->assertSuccessful();
    $this->actingAs($receptionist)->get('/exam-rooms')->assertSuccessful();
    $this->actingAs($clinician)->get('/exam-rooms')->assertSuccessful();

    $this->actingAs($admin)->get('/exam-rooms/create')->assertSuccessful();
    $this->actingAs($receptionist)->get('/exam-rooms/create')->assertForbidden();
    $this->actingAs($clinician)->get('/exam-rooms/create')->assertForbidden();
});
