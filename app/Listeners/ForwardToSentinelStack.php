<?php

namespace App\Listeners;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentScheduled;
use App\Events\AppointmentUpdated;
use App\Events\PatientCreated;
use App\Events\PatientUpdated;
use App\Events\RoomAssigned;
use App\Services\Integration\EventEnvelopeBuilder;
use App\Services\Integration\SentinelStackClientInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ForwardToSentinelStack implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected SentinelStackClientInterface $sentinelStackClient,
        protected EventEnvelopeBuilder $envelopeBuilder
    ) {}

    public function handle(PatientCreated|PatientUpdated|AppointmentScheduled|AppointmentUpdated|AppointmentCancelled|RoomAssigned $event): void
    {
        $payload = match (true) {
            $event instanceof PatientCreated => [
                'event_subtype' => 'patient.created',
                'patient_id' => $event->patient->id,
                'medical_record_number' => $event->patient->medical_record_number,
            ],
            $event instanceof PatientUpdated => [
                'event_subtype' => 'patient.updated',
                'patient_id' => $event->patient->id,
                'medical_record_number' => $event->patient->medical_record_number,
            ],
            $event instanceof AppointmentScheduled => [
                'event_subtype' => 'appointment.scheduled',
                'appointment_id' => $event->appointment->id,
                'patient_id' => $event->appointment->patient_id,
                'clinician_id' => $event->appointment->user_id,
            ],
            $event instanceof AppointmentUpdated => [
                'event_subtype' => 'appointment.updated',
                'appointment_id' => $event->appointment->id,
            ],
            $event instanceof AppointmentCancelled => [
                'event_subtype' => 'appointment.cancelled',
                'appointment_id' => $event->appointment->id,
                'patient_id' => $event->appointment->patient_id,
            ],
            $event instanceof RoomAssigned => [
                'event_subtype' => 'room.assigned',
                'appointment_id' => $event->appointment->id,
                'room_id' => $event->appointment->exam_room_id,
            ],
        };

        $envelope = $this->envelopeBuilder->buildEnvelope('domain_event', $payload);

        $this->sentinelStackClient->ingestEvent($envelope);
    }
}
