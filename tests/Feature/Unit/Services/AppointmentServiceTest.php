<?php

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->service = app(AppointmentService::class);
    $this->patient = Patient::factory()->create();
    $this->clinician = User::factory()->create();
});

it('can schedule an appointment', function () {
    $date = Carbon::now()->addDays(5);
    $data = [
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => '10:00:00',
        'duration_minutes' => 30,
        'appointment_type' => AppointmentType::Routine,
    ];

    $appointment = $this->service->scheduleAppointment($data);

    expect($appointment)->toBeInstanceOf(Appointment::class)
        ->and($appointment->patient_id)->toBe($this->patient->id)
        ->and($appointment->user_id)->toBe($this->clinician->id)
        ->and($appointment->status)->toBe(AppointmentStatus::Scheduled);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'create',
        'resource_type' => 'Appointment',
        'resource_id' => $appointment->id,
    ]);
});

it('prevents scheduling overlapping appointments for same clinician', function () {
    $date = Carbon::now()->addDays(5);
    $existingTime = Carbon::createFromTime(10, 0);

    Appointment::factory()->create([
        'user_id' => $this->clinician->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => $existingTime->format('H:i:s'),
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $overlappingTime = $existingTime->copy()->addMinutes(15);
    $data = [
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => $overlappingTime->format('H:i:s'),
        'duration_minutes' => 30,
        'appointment_type' => AppointmentType::Routine,
    ];

    expect(fn () => $this->service->scheduleAppointment($data))
        ->toThrow(\RuntimeException::class, 'Clinician is not available');
});

it('allows non-overlapping appointments for same clinician', function () {
    $date = Carbon::now()->addDays(5);

    Appointment::factory()->create([
        'user_id' => $this->clinician->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => '10:00:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $data = [
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => '11:00:00',
        'duration_minutes' => 30,
        'appointment_type' => AppointmentType::Routine,
    ];

    $appointment = $this->service->scheduleAppointment($data);

    expect($appointment)->toBeInstanceOf(Appointment::class);
});

it('can update appointment status', function () {
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Scheduled,
    ]);

    $updated = $this->service->updateAppointmentStatus(
        $appointment,
        AppointmentStatus::InProgress
    );

    expect($updated->status)->toBe(AppointmentStatus::InProgress);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'update',
        'resource_type' => 'Appointment',
        'resource_id' => $appointment->id,
    ]);
});

it('can cancel an appointment with reason', function () {
    $appointment = Appointment::factory()->create([
        'status' => AppointmentStatus::Scheduled,
    ]);

    $cancelled = $this->service->cancelAppointment($appointment, 'Patient request');

    expect($cancelled->status)->toBe(AppointmentStatus::Cancelled)
        ->and($cancelled->cancellation_reason)->toBe('Patient request')
        ->and($cancelled->cancelled_at)->not->toBeNull();
});

it('can assign room to appointment', function () {
    $room = ExamRoom::factory()->create(['is_active' => true]);
    $appointment = Appointment::factory()->create([
        'exam_room_id' => null,
    ]);

    $updated = $this->service->assignRoom($appointment, $room);

    expect($updated->exam_room_id)->toBe($room->id);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'update',
        'resource_type' => 'Appointment',
        'resource_id' => $appointment->id,
    ]);
});

it('prevents assigning inactive room', function () {
    $room = ExamRoom::factory()->inactive()->create();
    $appointment = Appointment::factory()->create();

    expect(fn () => $this->service->assignRoom($appointment, $room))
        ->toThrow(\RuntimeException::class, 'Room is not active');
});

it('prevents assigning room with conflicting appointment', function () {
    $room = ExamRoom::factory()->create(['is_active' => true]);
    $date = Carbon::now()->addDays(5);
    $existingTime = Carbon::createFromTime(10, 0);

    Appointment::factory()->create([
        'exam_room_id' => $room->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => $existingTime->format('H:i:s'),
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $overlappingTime = $existingTime->copy()->addMinutes(15);
    $appointment = Appointment::factory()->create([
        'appointment_date' => $date->toDateString(),
        'appointment_time' => $overlappingTime->format('H:i:s'),
        'duration_minutes' => 30,
    ]);

    expect(fn () => $this->service->assignRoom($appointment, $room))
        ->toThrow(\RuntimeException::class, 'Room is not available');
});

it('checks clinician availability correctly', function () {
    $date = Carbon::now()->addDays(5);
    $time = Carbon::createFromTime(10, 0);

    Appointment::factory()->create([
        'user_id' => $this->clinician->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => $time->format('H:i:s'),
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $available = $this->service->checkClinicianAvailability(
        $this->clinician->id,
        $date,
        $time->copy()->addMinutes(15),
        30
    );

    expect($available)->toBeFalse();

    $available2 = $this->service->checkClinicianAvailability(
        $this->clinician->id,
        $date,
        $time->copy()->addHours(2),
        30
    );

    expect($available2)->toBeTrue();
});

it('can exclude appointment from availability check', function () {
    $date = Carbon::now()->addDays(5);
    $time = Carbon::createFromTime(10, 0);

    $existingAppointment = Appointment::factory()->create([
        'user_id' => $this->clinician->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => $time->format('H:i:s'),
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $available = $this->service->checkClinicianAvailability(
        $this->clinician->id,
        $date,
        $time,
        30,
        $existingAppointment->id
    );

    expect($available)->toBeTrue();
});
