<?php

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->organization = \App\Models\Organization::factory()->create();
});

it('has fillable attributes', function () {
    $appointment = new Appointment;
    $fillable = [
        'organization_id',
        'patient_id',
        'user_id',
        'exam_room_id',
        'appointment_date',
        'appointment_time',
        'duration_minutes',
        'appointment_type',
        'status',
        'notes',
        'cancelled_at',
        'cancellation_reason',
    ];

    expect($appointment->getFillable())->toBe($fillable);
});

it('casts appointment_type to enum', function () {
    $appointment = Appointment::factory()->for($this->organization)->create(['appointment_type' => AppointmentType::Routine]);

    expect($appointment->appointment_type)->toBe(AppointmentType::Routine);
});

it('casts status to enum', function () {
    $appointment = Appointment::factory()->for($this->organization)->create(['status' => AppointmentStatus::Scheduled]);

    expect($appointment->status)->toBe(AppointmentStatus::Scheduled);
});

it('casts appointment_date to date', function () {
    $appointment = Appointment::factory()->for($this->organization)->create(['appointment_date' => '2024-12-25']);

    expect($appointment->appointment_date)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('has patient relationship', function () {
    $patient = Patient::factory()->for($this->organization)->create();
    $appointment = Appointment::factory()->for($this->organization)->create(['patient_id' => $patient->id]);

    expect($appointment->patient)->toBeInstanceOf(Patient::class)
        ->and($appointment->patient->id)->toBe($patient->id);
});

it('has user relationship', function () {
    $user = User::factory()->create();
    $appointment = Appointment::factory()->for($this->organization)->create(['user_id' => $user->id]);

    expect($appointment->user)->toBeInstanceOf(User::class)
        ->and($appointment->user->id)->toBe($user->id);
});

it('has exam_room relationship', function () {
    $room = ExamRoom::factory()->for($this->organization)->create();
    $appointment = Appointment::factory()->for($this->organization)->create(['exam_room_id' => $room->id]);

    expect($appointment->examRoom)->toBeInstanceOf(ExamRoom::class)
        ->and($appointment->examRoom->id)->toBe($room->id);
});

it('allows nullable exam_room_id', function () {
    $appointment = Appointment::factory()->for($this->organization)->create(['exam_room_id' => null]);

    expect($appointment->examRoom)->toBeNull();
});

it('can scope upcoming appointments', function () {
    $futureDate = Carbon::now()->addDays(5)->toDateString();
    $pastDate = Carbon::now()->subDays(5)->toDateString();

    Appointment::factory()->for($this->organization)->create([
        'appointment_date' => $futureDate,
        'status' => AppointmentStatus::Scheduled,
    ]);
    Appointment::factory()->for($this->organization)->create([
        'appointment_date' => $pastDate,
        'status' => AppointmentStatus::Scheduled,
    ]);
    Appointment::factory()->for($this->organization)->create([
        'appointment_date' => $futureDate,
        'status' => AppointmentStatus::Completed,
    ]);

    $upcoming = Appointment::upcoming()->get();

    expect($upcoming)->toHaveCount(1);
});

it('can scope by status', function () {
    Appointment::factory()->for($this->organization)->count(2)->create(['status' => AppointmentStatus::Scheduled]);
    Appointment::factory()->for($this->organization)->create(['status' => AppointmentStatus::Completed]);

    $scheduled = Appointment::byStatus(AppointmentStatus::Scheduled)->get();

    expect($scheduled)->toHaveCount(2);
});

it('can scope by date range', function () {
    $start = Carbon::now()->subDays(10);
    $end = Carbon::now()->addDays(10);

    Appointment::factory()->for($this->organization)->create(['appointment_date' => $start->copy()->subDays(5)]);
    Appointment::factory()->for($this->organization)->create(['appointment_date' => $start->copy()->addDays(2)]);
    Appointment::factory()->for($this->organization)->create(['appointment_date' => $end->copy()->addDays(5)]);

    $inRange = Appointment::byDateRange($start, $end)->get();

    expect($inRange)->toHaveCount(1);
});

it('can scope by clinician', function () {
    $user = User::factory()->create();
    Appointment::factory()->for($this->organization)->count(2)->create(['user_id' => $user->id]);
    Appointment::factory()->for($this->organization)->create();

    $userAppointments = Appointment::byClinician($user->id)->get();

    expect($userAppointments)->toHaveCount(2);
});

it('can scope by patient', function () {
    $patient = Patient::factory()->for($this->organization)->create();
    Appointment::factory()->for($this->organization)->count(3)->create(['patient_id' => $patient->id]);
    Appointment::factory()->for($this->organization)->create();

    $patientAppointments = Appointment::byPatient($patient->id)->get();

    expect($patientAppointments)->toHaveCount(3);
});
