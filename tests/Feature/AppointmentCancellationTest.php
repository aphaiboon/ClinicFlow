<?php

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('completes full appointment cancellation flow', function () {
    $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
    $clinician = User::factory()->create(['role' => UserRole::Clinician]);
    $patient = Patient::factory()->create();

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'user_id' => $clinician->id,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = $this->actingAs($receptionist)
        ->get("/appointments/{$appointment->id}");

    $response->assertSuccessful();

    $response = $this->actingAs($receptionist)
        ->post("/appointments/{$appointment->id}/cancel", [
            'reason' => 'Patient request',
        ]);

    $response->assertRedirect();

    $appointment->refresh();

    expect($appointment->status)->toBe(AppointmentStatus::Cancelled)
        ->and($appointment->cancellation_reason)->toBe('Patient request')
        ->and($appointment->cancelled_at)->not->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'update',
        'resource_type' => 'Appointment',
        'resource_id' => $appointment->id,
    ]);
});

it('prevents cancelling non-scheduled appointments', function () {
    $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
    $clinician = User::factory()->create(['role' => UserRole::Clinician]);
    $patient = Patient::factory()->create();

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'user_id' => $clinician->id,
        'status' => AppointmentStatus::Completed,
    ]);

    $response = $this->actingAs($receptionist)
        ->post("/appointments/{$appointment->id}/cancel", [
            'reason' => 'Should not work',
        ]);

    $response->assertForbidden();

    $appointment->refresh();
    expect($appointment->status)->toBe(AppointmentStatus::Completed);
});
