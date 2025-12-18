<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Organization;
use App\Models\Patient;
use App\Services\PatientAppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create(['name' => 'ABC Clinic']);
    $this->patient = Patient::factory()->for($this->organization)->create([
        'email' => 'patient@abc-clinic.test',
    ]);
    $this->service = app(PatientAppointmentService::class);
});

it('can get patient appointments with filters', function () {
    $upcoming = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(5),
        'status' => AppointmentStatus::Scheduled,
    ]);
    $past = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->subDays(5),
        'status' => AppointmentStatus::Completed,
    ]);

    $appointments = $this->service->getPatientAppointments($this->patient, []);

    expect($appointments)->toHaveCount(2)
        ->and($appointments->pluck('id')->toArray())->toContain($upcoming->id, $past->id);
});

it('filters appointments by status', function () {
    Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'status' => AppointmentStatus::Scheduled,
    ]);
    Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'status' => AppointmentStatus::Cancelled,
    ]);

    $appointments = $this->service->getPatientAppointments($this->patient, ['status' => 'scheduled']);

    expect($appointments)->toHaveCount(1)
        ->and($appointments->first()->status)->toBe(AppointmentStatus::Scheduled);
});

it('can check if patient can cancel appointment', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(2)->toDateString(),
        'appointment_time' => '10:00:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    $canCancel = $this->service->canCancelAppointment($this->patient, $appointment);

    expect($canCancel)->toBeTrue();
});

it('cannot cancel appointment within 24 hours', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addHours(12)->toDateString(),
        'appointment_time' => now()->addHours(12)->format('H:i:s'),
        'status' => AppointmentStatus::Scheduled,
    ]);

    $canCancel = $this->service->canCancelAppointment($this->patient, $appointment);

    expect($canCancel)->toBeFalse();
});

it('cannot cancel already cancelled appointment', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'status' => AppointmentStatus::Cancelled,
    ]);

    $canCancel = $this->service->canCancelAppointment($this->patient, $appointment);

    expect($canCancel)->toBeFalse();
});

it('can cancel appointment with valid reason', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addDays(2)->toDateString(),
        'appointment_time' => '10:00:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    $this->service->cancelAppointment($this->patient, $appointment, 'Patient request');

    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Cancelled)
        ->and($appointment->fresh()->cancellation_reason)->toBe('Patient request');
});

it('throws exception when canceling appointment too close to appointment time', function () {
    $appointment = Appointment::factory()->for($this->organization)->for($this->patient)->create([
        'appointment_date' => now()->addHours(12)->toDateString(),
        'appointment_time' => now()->addHours(12)->format('H:i:s'),
        'status' => AppointmentStatus::Scheduled,
    ]);

    expect(fn () => $this->service->cancelAppointment($this->patient, $appointment, 'Reason'))
        ->toThrow(\RuntimeException::class);
});
