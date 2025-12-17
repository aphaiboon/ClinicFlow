<?php

use App\Events\AppointmentScheduled;
use App\Events\PatientCreated;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('is queued', function () {
    $listener = new \App\Listeners\ForwardToSentinelStack;

    expect($listener)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

it('handles PatientCreated event without errors', function () {
    Queue::fake();

    $patient = Patient::factory()->create();
    $event = new PatientCreated($patient);
    $listener = new \App\Listeners\ForwardToSentinelStack;

    $listener->handle($event);

    expect(true)->toBeTrue();
});

it('handles AppointmentScheduled event without errors', function () {
    Queue::fake();

    $appointment = Appointment::factory()->create();
    $event = new AppointmentScheduled($appointment);
    $listener = new \App\Listeners\ForwardToSentinelStack;

    $listener->handle($event);

    expect(true)->toBeTrue();
});
