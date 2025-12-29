<?php

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentCalendarFormatter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->patient = Patient::factory()->for($this->organization)->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
    $this->clinician = User::factory()->create(['name' => 'Dr. Smith']);
    $this->examRoom = ExamRoom::factory()->for($this->organization)->create([
        'name' => 'Room A',
        'room_number' => '101',
    ]);
    $this->formatter = new AppointmentCalendarFormatter;
});

test('formats appointment with all fields for calendar', function () {
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => '2025-12-30',
        'appointment_time' => '10:00:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
        'appointment_type' => AppointmentType::Routine,
        'notes' => 'Regular checkup',
    ]);

    $event = $this->formatter->format($appointment);

    expect($event)->toBeArray()
        ->and($event['id'])->toBe("appointment-{$appointment->id}")
        ->and($event['title'])->toBe('John Doe')
        ->and($event['start'])->toBeString()
        ->and($event['end'])->toBeString()
        ->and($event['backgroundColor'])->toBe('#3b82f6') // Blue for scheduled
        ->and($event['borderColor'])->toBe('#3b82f6')
        ->and($event['textColor'])->toBe('#ffffff')
        ->and($event['extendedProps'])->toBeArray()
        ->and($event['extendedProps']['appointmentId'])->toBe($appointment->id)
        ->and($event['extendedProps']['patientName'])->toBe('John Doe')
        ->and($event['extendedProps']['clinicianName'])->toBe('Dr. Smith')
        ->and($event['extendedProps']['examRoomName'])->toBe('Room A')
        ->and($event['extendedProps']['status'])->toBe('scheduled')
        ->and($event['extendedProps']['appointmentType'])->toBe('routine')
        ->and($event['extendedProps']['durationMinutes'])->toBe(30)
        ->and($event['extendedProps']['notes'])->toBe('Regular checkup');
});

test('formats appointment with correct start and end times', function () {
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'appointment_date' => '2025-12-30',
        'appointment_time' => '14:30:00',
        'duration_minutes' => 45,
    ]);

    $event = $this->formatter->format($appointment);

    $start = Carbon::parse($event['start']);
    $end = Carbon::parse($event['end']);

    expect($start->format('Y-m-d H:i:s'))->toBe('2025-12-30 14:30:00')
        ->and($end->format('Y-m-d H:i:s'))->toBe('2025-12-30 15:15:00');
});

test('uses correct color for scheduled status', function () {
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $event = $this->formatter->format($appointment);

    expect($event['backgroundColor'])->toBe('#3b82f6'); // Blue
});

test('uses correct color for in_progress status', function () {
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'status' => AppointmentStatus::InProgress,
    ]);

    $event = $this->formatter->format($appointment);

    expect($event['backgroundColor'])->toBe('#f97316'); // Orange
});

test('uses correct color for completed status', function () {
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'status' => AppointmentStatus::Completed,
    ]);

    $event = $this->formatter->format($appointment);

    expect($event['backgroundColor'])->toBe('#22c55e'); // Green
});

test('uses correct color for no_show status', function () {
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'status' => AppointmentStatus::NoShow,
    ]);

    $event = $this->formatter->format($appointment);

    expect($event['backgroundColor'])->toBe('#ef4444'); // Red
});

test('handles missing patient gracefully', function () {
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'status' => AppointmentStatus::Scheduled,
    ]);

    // Remove patient relationship to simulate missing patient
    $appointment->setRelation('patient', null);

    $event = $this->formatter->format($appointment);

    expect($event['title'])->toBe('Unknown Patient')
        ->and($event['extendedProps']['patientName'])->toBe('Unknown Patient');
});

test('handles missing clinician gracefully', function () {
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
    ]);

    // Remove user relationship to simulate missing clinician
    $appointment->setRelation('user', null);

    $event = $this->formatter->format($appointment);

    expect($event['extendedProps']['clinicianName'])->toBe('Unknown Clinician');
});

test('handles missing exam room gracefully', function () {
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'exam_room_id' => null,
    ]);

    $event = $this->formatter->format($appointment);

    expect($event['extendedProps']['examRoomName'])->toBeNull();
});
