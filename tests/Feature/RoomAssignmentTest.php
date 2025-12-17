<?php

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('completes full room assignment flow', function () {
    $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
    $clinician = User::factory()->create(['role' => UserRole::Clinician]);
    $patient = Patient::factory()->create();
    $room = ExamRoom::factory()->create(['is_active' => true]);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'user_id' => $clinician->id,
        'exam_room_id' => null,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = $this->actingAs($receptionist)
        ->post("/appointments/{$appointment->id}/assign-room", [
            'exam_room_id' => $room->id,
        ]);

    $response->assertRedirect();

    $appointment->refresh();

    expect($appointment->exam_room_id)->toBe($room->id);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'update',
        'resource_type' => 'App\\Models\\Appointment',
        'resource_id' => $appointment->id,
    ]);
});

it('prevents assigning room with scheduling conflict', function () {
    $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
    $clinician1 = User::factory()->create(['role' => UserRole::Clinician]);
    $clinician2 = User::factory()->create(['role' => UserRole::Clinician]);
    $patient1 = Patient::factory()->create();
    $patient2 = Patient::factory()->create();
    $room = ExamRoom::factory()->create(['is_active' => true]);

    $existingAppointment = Appointment::factory()->create([
        'exam_room_id' => $room->id,
        'appointment_date' => Carbon::tomorrow()->toDateString(),
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient2->id,
        'user_id' => $clinician2->id,
        'exam_room_id' => null,
        'appointment_date' => $existingAppointment->appointment_date,
        'appointment_time' => '10:15',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = $this->actingAs($receptionist)
        ->post("/appointments/{$appointment->id}/assign-room", [
            'exam_room_id' => $room->id,
        ]);

    $response->assertSessionHasErrors(['error']);

    $appointment->refresh();
    expect($appointment->exam_room_id)->toBeNull();
});
