<?php

use App\Events\AppointmentCancelled;
use App\Events\AppointmentScheduled;
use App\Events\AppointmentUpdated;
use App\Events\PatientCreated;
use App\Events\PatientUpdated;
use App\Events\RoomAssigned;
use App\Listeners\ForwardToSentinelStack;
use App\Models\Appointment;
use App\Models\Patient;
use App\Services\Integration\SentinelStackClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

it('forwards PatientCreated event to SentinelStack', function () {
    $client = mock(SentinelStackClientInterface::class);
    $client->shouldReceive('sendEvent')
        ->once()
        ->with('patient.created', \Mockery::on(fn ($data) => isset($data['patient_id']) && isset($data['medical_record_number'])));

    $this->app->instance(SentinelStackClientInterface::class, $client);

    $patient = Patient::factory()->create();
    $event = new PatientCreated($patient);
    $listener = new ForwardToSentinelStack($client);

    $listener->handle($event);
});

it('forwards PatientUpdated event to SentinelStack', function () {
    $client = mock(SentinelStackClientInterface::class);
    $client->shouldReceive('sendEvent')
        ->once()
        ->with('patient.updated', \Mockery::on(fn ($data) => isset($data['patient_id'])));

    $this->app->instance(SentinelStackClientInterface::class, $client);

    $patient = Patient::factory()->create();
    $event = new PatientUpdated($patient);
    $listener = new ForwardToSentinelStack($client);

    $listener->handle($event);
});

it('forwards AppointmentScheduled event to SentinelStack', function () {
    $client = mock(SentinelStackClientInterface::class);
    $client->shouldReceive('sendMetric')
        ->once()
        ->with('appointment.scheduled', 1.0, \Mockery::type('array'));

    $this->app->instance(SentinelStackClientInterface::class, $client);

    $appointment = Appointment::factory()->create();
    $event = new AppointmentScheduled($appointment);
    $listener = new ForwardToSentinelStack($client);

    $listener->handle($event);
});

it('forwards AppointmentUpdated event to SentinelStack', function () {
    $client = mock(SentinelStackClientInterface::class);
    $client->shouldReceive('sendEvent')
        ->once()
        ->with('appointment.updated', \Mockery::on(fn ($data) => isset($data['appointment_id'])));

    $this->app->instance(SentinelStackClientInterface::class, $client);

    $appointment = Appointment::factory()->create();
    $event = new AppointmentUpdated($appointment);
    $listener = new ForwardToSentinelStack($client);

    $listener->handle($event);
});

it('forwards AppointmentCancelled event to SentinelStack', function () {
    $client = mock(SentinelStackClientInterface::class);
    $client->shouldReceive('logIncident')
        ->once()
        ->with('appointment.cancelled', 'Appointment cancelled', \Mockery::type('array'));

    $this->app->instance(SentinelStackClientInterface::class, $client);

    $appointment = Appointment::factory()->create();
    $event = new AppointmentCancelled($appointment);
    $listener = new ForwardToSentinelStack($client);

    $listener->handle($event);
});

it('forwards RoomAssigned event to SentinelStack', function () {
    $client = mock(SentinelStackClientInterface::class);
    $client->shouldReceive('sendMetric')
        ->once()
        ->with('room.assigned', 1.0, \Mockery::type('array'));

    $this->app->instance(SentinelStackClientInterface::class, $client);

    $appointment = Appointment::factory()->create();
    $event = new RoomAssigned($appointment);
    $listener = new ForwardToSentinelStack($client);

    $listener->handle($event);
});
