<?php

use App\Enums\AppointmentStatus;
use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->receptionist = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->clinician = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->organization->users()->attach($this->receptionist->id, [
        'role' => OrganizationRole::Receptionist->value,
        'joined_at' => now(),
    ]);
    $this->organization->users()->attach($this->clinician->id, [
        'role' => OrganizationRole::Clinician->value,
        'joined_at' => now(),
    ]);
});

it('completes full room assignment flow', function () {
    $patient = Patient::factory()->for($this->organization)->create();
    $room = ExamRoom::factory()->for($this->organization)->create(['is_active' => true]);

    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $patient->id,
        'user_id' => $this->clinician->id,
        'exam_room_id' => null,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = $this->actingAs($this->receptionist)
        ->post("/appointments/{$appointment->id}/assign-room", [
            'exam_room_id' => $room->id,
        ]);

    $response->assertRedirect();

    $appointment->refresh();

    expect($appointment->exam_room_id)->toBe($room->id);

    $this->assertDatabaseHas('audit_logs', [
        'organization_id' => $this->organization->id,
        'action' => 'update',
        'resource_type' => 'Appointment',
        'resource_id' => $appointment->id,
    ]);
});

it('prevents assigning room with scheduling conflict', function () {
    $clinician2 = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->organization->users()->attach($clinician2->id, [
        'role' => OrganizationRole::Clinician->value,
        'joined_at' => now(),
    ]);
    $patient1 = Patient::factory()->for($this->organization)->create();
    $patient2 = Patient::factory()->for($this->organization)->create();
    $room = ExamRoom::factory()->for($this->organization)->create(['is_active' => true]);

    $existingAppointment = Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $room->id,
        'appointment_date' => Carbon::tomorrow()->toDateString(),
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $patient2->id,
        'user_id' => $clinician2->id,
        'exam_room_id' => null,
        'appointment_date' => $existingAppointment->appointment_date,
        'appointment_time' => '10:15',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = $this->actingAs($this->receptionist)
        ->post("/appointments/{$appointment->id}/assign-room", [
            'exam_room_id' => $room->id,
        ]);

    $response->assertSessionHasErrors(['error']);

    $appointment->refresh();
    expect($appointment->exam_room_id)->toBeNull();
});
