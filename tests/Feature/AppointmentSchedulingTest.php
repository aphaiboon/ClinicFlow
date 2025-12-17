<?php

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('completes full appointment scheduling flow', function () {
    $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
    $clinician = User::factory()->create(['role' => UserRole::Clinician]);
    $patient = Patient::factory()->create();
    $room = ExamRoom::factory()->create(['is_active' => true]);

    $appointmentDate = Carbon::tomorrow();
    $appointmentTime = '10:00';

    $response = $this->actingAs($receptionist)
        ->get('/appointments/create');

    $response->assertSuccessful();

    $appointmentData = [
        'patient_id' => $patient->id,
        'user_id' => $clinician->id,
        'exam_room_id' => $room->id,
        'appointment_date' => $appointmentDate->toDateString(),
        'appointment_time' => $appointmentTime,
        'duration_minutes' => 30,
        'appointment_type' => AppointmentType::Routine->value,
        'notes' => 'Regular checkup',
    ];

    $response = $this->actingAs($receptionist)
        ->post('/appointments', $appointmentData);

    $response->assertRedirect();

    $appointment = Appointment::where('patient_id', $patient->id)
        ->whereDate('appointment_date', $appointmentDate->toDateString())
        ->first();

    expect($appointment)->not->toBeNull()
        ->and($appointment->status)->toBe(AppointmentStatus::Scheduled)
        ->and($appointment->user_id)->toBe($clinician->id)
        ->and($appointment->exam_room_id)->toBe($room->id)
        ->and($appointment->notes)->toBe('Regular checkup');

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'create',
        'resource_type' => 'Appointment',
        'resource_id' => $appointment->id,
    ]);
});

it('prevents scheduling conflicting appointments for same clinician', function () {
    $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
    $clinician = User::factory()->create(['role' => UserRole::Clinician]);
    $patient1 = Patient::factory()->create();
    $patient2 = Patient::factory()->create();

    $appointmentDate = Carbon::tomorrow();
    $appointmentTime = '10:00';

    Appointment::factory()->create([
        'user_id' => $clinician->id,
        'appointment_date' => $appointmentDate->toDateString(),
        'appointment_time' => $appointmentTime,
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $conflictingData = [
        'patient_id' => $patient2->id,
        'user_id' => $clinician->id,
        'appointment_date' => $appointmentDate->toDateString(),
        'appointment_time' => '10:15',
        'duration_minutes' => 30,
        'appointment_type' => AppointmentType::Routine->value,
    ];

    $response = $this->actingAs($receptionist)
        ->post('/appointments', $conflictingData);

    $response->assertSessionHasErrors(['error']);

    expect(Appointment::where('patient_id', $patient2->id)->count())->toBe(0);
});

it('prevents scheduling conflicting appointments for same room', function () {
    $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
    $clinician1 = User::factory()->create(['role' => UserRole::Clinician]);
    $clinician2 = User::factory()->create(['role' => UserRole::Clinician]);
    $patient1 = Patient::factory()->create();
    $patient2 = Patient::factory()->create();
    $room = ExamRoom::factory()->create(['is_active' => true]);

    $appointmentDate = Carbon::tomorrow();
    $appointmentTime = '10:00';

    Appointment::factory()->create([
        'exam_room_id' => $room->id,
        'appointment_date' => $appointmentDate->toDateString(),
        'appointment_time' => $appointmentTime,
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $conflictingData = [
        'patient_id' => $patient2->id,
        'user_id' => $clinician2->id,
        'exam_room_id' => $room->id,
        'appointment_date' => $appointmentDate->toDateString(),
        'appointment_time' => '10:15',
        'duration_minutes' => 30,
        'appointment_type' => AppointmentType::Routine->value,
    ];

    $response = $this->actingAs($receptionist)
        ->post('/appointments', $conflictingData);

    $response->assertSessionHasErrors(['error']);

    expect(Appointment::where('patient_id', $patient2->id)->count())->toBe(0);
});
