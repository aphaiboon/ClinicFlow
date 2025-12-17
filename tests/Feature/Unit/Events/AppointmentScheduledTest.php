<?php

use App\Events\AppointmentScheduled;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be instantiated with an appointment', function () {
    $appointment = Appointment::factory()->create();
    $event = new AppointmentScheduled($appointment);

    expect($event->appointment)->toBe($appointment);
});

it('contains the appointment model', function () {
    $appointment = Appointment::factory()->create();
    $event = new AppointmentScheduled($appointment);

    expect($event->appointment->id)->toBe($appointment->id);
});
