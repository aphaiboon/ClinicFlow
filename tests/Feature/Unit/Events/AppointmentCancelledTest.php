<?php

use App\Events\AppointmentCancelled;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be instantiated with an appointment', function () {
    $appointment = Appointment::factory()->create();
    $event = new AppointmentCancelled($appointment);

    expect($event->appointment)->toBe($appointment);
});
