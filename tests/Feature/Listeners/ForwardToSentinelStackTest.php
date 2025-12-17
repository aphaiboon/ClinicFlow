<?php

use App\Events\AppointmentCancelled;
use App\Events\AppointmentScheduled;
use App\Events\AppointmentUpdated;
use App\Events\PatientCreated;
use App\Events\PatientUpdated;
use App\Events\RoomAssigned;
use App\Listeners\ForwardToSentinelStack;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Patient;
use App\Services\Integration\EventEnvelopeBuilder;
use App\Services\Integration\SentinelStackClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->envelopeBuilder = mock(EventEnvelopeBuilder::class);
    $this->client = mock(SentinelStackClientInterface::class);
    $this->listener = new ForwardToSentinelStack($this->client, $this->envelopeBuilder);

    $request = Request::create('/test', 'GET');
    $request->attributes->set('request_id', 'req_123');
    $request->attributes->set('trace_id', 'trace_456');
    app()->instance('request', $request);
});

it('maps PatientCreated to domain_event with correct event_subtype', function () {
    $patient = Patient::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    $event = new PatientCreated($patient);

    $expectedEnvelope = [
        'event_type' => 'domain_event',
        'payload' => [
            'event_subtype' => 'patient.created',
            'patient_id' => $patient->id,
            'medical_record_number' => $patient->medical_record_number,
        ],
    ];

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('domain_event', \Mockery::on(function ($payload) use ($patient) {
            return $payload['event_subtype'] === 'patient.created'
                && $payload['patient_id'] === $patient->id
                && $payload['medical_record_number'] === $patient->medical_record_number
                && ! isset($payload['first_name'])
                && ! isset($payload['last_name']);
        }))
        ->andReturn($expectedEnvelope);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->with($expectedEnvelope)
        ->andReturn(true);

    $this->listener->handle($event);
});

it('maps PatientUpdated to domain_event with correct event_subtype', function () {
    $patient = Patient::factory()->create();
    $event = new PatientUpdated($patient);

    $expectedEnvelope = [
        'event_type' => 'domain_event',
        'payload' => [
            'event_subtype' => 'patient.updated',
            'patient_id' => $patient->id,
        ],
    ];

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('domain_event', \Mockery::on(function ($payload) use ($patient) {
            return $payload['event_subtype'] === 'patient.updated'
                && $payload['patient_id'] === $patient->id;
        }))
        ->andReturn($expectedEnvelope);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->with($expectedEnvelope)
        ->andReturn(true);

    $this->listener->handle($event);
});

it('maps AppointmentScheduled to domain_event with correct event_subtype', function () {
    $appointment = Appointment::factory()->create();
    $event = new AppointmentScheduled($appointment);

    $expectedEnvelope = [
        'event_type' => 'domain_event',
        'payload' => [
            'event_subtype' => 'appointment.scheduled',
            'appointment_id' => $appointment->id,
        ],
    ];

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('domain_event', \Mockery::on(function ($payload) use ($appointment) {
            return $payload['event_subtype'] === 'appointment.scheduled'
                && $payload['appointment_id'] === $appointment->id;
        }))
        ->andReturn($expectedEnvelope);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->with($expectedEnvelope)
        ->andReturn(true);

    $this->listener->handle($event);
});

it('maps AppointmentUpdated to domain_event with correct event_subtype', function () {
    $appointment = Appointment::factory()->create();
    $event = new AppointmentUpdated($appointment);

    $expectedEnvelope = [
        'event_type' => 'domain_event',
        'payload' => [
            'event_subtype' => 'appointment.updated',
            'appointment_id' => $appointment->id,
        ],
    ];

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('domain_event', \Mockery::on(function ($payload) {
            return $payload['event_subtype'] === 'appointment.updated';
        }))
        ->andReturn($expectedEnvelope);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->with($expectedEnvelope)
        ->andReturn(true);

    $this->listener->handle($event);
});

it('maps AppointmentCancelled to domain_event with correct event_subtype', function () {
    $appointment = Appointment::factory()->create(['cancellation_reason' => 'Patient request']);
    $event = new AppointmentCancelled($appointment);

    $expectedEnvelope = [
        'event_type' => 'domain_event',
        'payload' => [
            'event_subtype' => 'appointment.cancelled',
            'appointment_id' => $appointment->id,
        ],
    ];

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('domain_event', \Mockery::on(function ($payload) use ($appointment) {
            return $payload['event_subtype'] === 'appointment.cancelled'
                && $payload['appointment_id'] === $appointment->id;
        }))
        ->andReturn($expectedEnvelope);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->with($expectedEnvelope)
        ->andReturn(true);

    $this->listener->handle($event);
});

it('maps RoomAssigned to domain_event with correct event_subtype', function () {
    $examRoom = ExamRoom::factory()->create();
    $appointment = Appointment::factory()->create(['exam_room_id' => $examRoom->id]);
    $event = new RoomAssigned($appointment);

    $expectedEnvelope = [
        'event_type' => 'domain_event',
        'payload' => [
            'event_subtype' => 'room.assigned',
            'appointment_id' => $appointment->id,
        ],
    ];

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('domain_event', \Mockery::on(function ($payload) use ($appointment, $examRoom) {
            return $payload['event_subtype'] === 'room.assigned'
                && $payload['appointment_id'] === $appointment->id
                && $payload['room_id'] === $examRoom->id;
        }))
        ->andReturn($expectedEnvelope);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->with($expectedEnvelope)
        ->andReturn(true);

    $this->listener->handle($event);
});

it('ensures PHI safety - no sensitive patient data in payloads', function () {
    $patient = Patient::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
        'phone' => '555-1234',
        'email' => 'john@example.com',
    ]);
    $event = new PatientCreated($patient);

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('domain_event', \Mockery::on(function ($payload) {
            return ! isset($payload['first_name'])
                && ! isset($payload['last_name'])
                && ! isset($payload['date_of_birth'])
                && ! isset($payload['phone'])
                && ! isset($payload['email'])
                && isset($payload['patient_id']);
        }))
        ->andReturn(['event_type' => 'domain_event', 'payload' => []]);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->andReturn(true);

    $this->listener->handle($event);
});

it('uses envelope builder to wrap payloads', function () {
    $patient = Patient::factory()->create();
    $event = new PatientCreated($patient);

    $fullEnvelope = [
        'event_id' => 'evt_123',
        'event_type' => 'domain_event',
        'timestamp' => now()->toIso8601String(),
        'service' => ['service_id' => 'clinicflow'],
        'environment' => 'production',
        'tenant_id' => 'tenant_123',
        'actor' => ['user_id' => '1'],
        'correlation' => ['request_id' => 'req_123'],
        'payload' => ['event_subtype' => 'patient.created'],
    ];

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->andReturn($fullEnvelope);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->with($fullEnvelope)
        ->andReturn(true);

    $this->listener->handle($event);
});
