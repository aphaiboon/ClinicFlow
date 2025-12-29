<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentConflictService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->patient = Patient::factory()->for($this->organization)->create();
    $this->clinician = User::factory()->create();
    $this->examRoom = ExamRoom::factory()->for($this->organization)->create();
    $this->service = new AppointmentConflictService;
});

test('detects no conflicts when clinician has no appointments', function () {
    $date = Carbon::today()->addDay();
    $time = Carbon::createFromTime(10, 0);
    $duration = 30;

    $hasConflict = $this->service->hasClinicianConflict(
        $this->clinician->id,
        $date,
        $time,
        $duration
    );

    expect($hasConflict)->toBeFalse();
});

test('detects conflict when clinician has overlapping appointment', function () {
    $date = Carbon::today()->addDay();
    $time = Carbon::createFromTime(10, 0);
    $duration = 30;

    // Create overlapping appointment
    Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $date,
        'appointment_time' => '10:15:00', // Overlaps with 10:00-10:30
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $hasConflict = $this->service->hasClinicianConflict(
        $this->clinician->id,
        $date,
        $time,
        $duration
    );

    expect($hasConflict)->toBeTrue();
});

test('excludes appointment from conflict check when excludeAppointmentId provided', function () {
    $date = Carbon::today()->addDay();
    $time = Carbon::createFromTime(10, 0);
    $duration = 30;

    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $date,
        'appointment_time' => '10:00:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $hasConflict = $this->service->hasClinicianConflict(
        $this->clinician->id,
        $date,
        $time,
        $duration,
        $appointment->id
    );

    expect($hasConflict)->toBeFalse();
});

test('ignores cancelled appointments when checking conflicts', function () {
    $date = Carbon::today()->addDay();
    $time = Carbon::createFromTime(10, 0);
    $duration = 30;

    Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $date,
        'appointment_time' => '10:15:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Cancelled,
    ]);

    $hasConflict = $this->service->hasClinicianConflict(
        $this->clinician->id,
        $date,
        $time,
        $duration
    );

    expect($hasConflict)->toBeFalse();
});

test('detects no conflicts when exam room has no appointments', function () {
    $date = Carbon::today()->addDay();
    $time = Carbon::createFromTime(10, 0);
    $duration = 30;

    $hasConflict = $this->service->hasRoomConflict(
        $this->examRoom->id,
        $date,
        $time,
        $duration
    );

    expect($hasConflict)->toBeFalse();
});

test('detects conflict when exam room has overlapping appointment', function () {
    $date = Carbon::today()->addDay();
    $time = Carbon::createFromTime(10, 0);
    $duration = 30;

    Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => $date,
        'appointment_time' => '10:15:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $hasConflict = $this->service->hasRoomConflict(
        $this->examRoom->id,
        $date,
        $time,
        $duration
    );

    expect($hasConflict)->toBeTrue();
});

test('excludes appointment from room conflict check when excludeAppointmentId provided', function () {
    $date = Carbon::today()->addDay();
    $time = Carbon::createFromTime(10, 0);
    $duration = 30;

    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => $date,
        'appointment_time' => '10:00:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $hasConflict = $this->service->hasRoomConflict(
        $this->examRoom->id,
        $date,
        $time,
        $duration,
        $appointment->id
    );

    expect($hasConflict)->toBeFalse();
});

test('finds conflicting appointments for clinician', function () {
    $date = Carbon::today()->addDay();
    $time = Carbon::createFromTime(10, 0);
    $duration = 30;

    $conflictingAppointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $date,
        'appointment_time' => '10:15:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $nonConflictingAppointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $date,
        'appointment_time' => '11:00:00', // No overlap
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $conflicts = $this->service->findClinicianConflicts(
        $this->clinician->id,
        $date,
        $time,
        $duration
    );

    expect($conflicts)->toHaveCount(1)
        ->and($conflicts->first()->id)->toBe($conflictingAppointment->id);
});

test('finds conflicting appointments for exam room', function () {
    $date = Carbon::today()->addDay();
    $time = Carbon::createFromTime(10, 0);
    $duration = 30;

    $conflictingAppointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => $date,
        'appointment_time' => '10:15:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $nonConflictingAppointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => $date,
        'appointment_time' => '11:00:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $conflicts = $this->service->findRoomConflicts(
        $this->examRoom->id,
        $date,
        $time,
        $duration
    );

    expect($conflicts)->toHaveCount(1)
        ->and($conflicts->first()->id)->toBe($conflictingAppointment->id);
});

test('checks if two time ranges overlap correctly', function () {
    // Test edge cases for overlap detection
    $service = new AppointmentConflictService;

    // Adjacent appointments (no overlap)
    $start1 = Carbon::createFromTime(10, 0);
    $end1 = Carbon::createFromTime(10, 30);
    $start2 = Carbon::createFromTime(10, 30);
    $end2 = Carbon::createFromTime(11, 0);

    expect($service->appointmentsOverlap($start1, $end1, $start2, $end2))->toBeFalse();

    // Overlapping appointments
    $start3 = Carbon::createFromTime(10, 0);
    $end3 = Carbon::createFromTime(10, 30);
    $start4 = Carbon::createFromTime(10, 15);
    $end4 = Carbon::createFromTime(10, 45);

    expect($service->appointmentsOverlap($start3, $end3, $start4, $end4))->toBeTrue();

    // One appointment contains another
    $start5 = Carbon::createFromTime(10, 0);
    $end5 = Carbon::createFromTime(11, 0);
    $start6 = Carbon::createFromTime(10, 15);
    $end6 = Carbon::createFromTime(10, 45);

    expect($service->appointmentsOverlap($start5, $end5, $start6, $end6))->toBeTrue();
});
