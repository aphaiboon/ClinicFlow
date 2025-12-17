<?php

namespace App\Listeners;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentScheduled;
use App\Events\AppointmentUpdated;
use App\Events\PatientCreated;
use App\Events\PatientUpdated;
use App\Events\RoomAssigned;
use App\Services\Integration\SentinelStackClientInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ForwardToSentinelStack implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected SentinelStackClientInterface $sentinelStackClient
    ) {}

    public function handle(PatientCreated|PatientUpdated|AppointmentScheduled|AppointmentUpdated|AppointmentCancelled|RoomAssigned $event): void
    {
        if ($event instanceof PatientCreated) {
            $this->sentinelStackClient->sendEvent('patient.created', [
                'patient_id' => $event->patient->id,
                'medical_record_number' => $event->patient->medical_record_number,
                'timestamp' => now()->toIso8601String(),
            ]);
        } elseif ($event instanceof PatientUpdated) {
            $this->sentinelStackClient->sendEvent('patient.updated', [
                'patient_id' => $event->patient->id,
                'medical_record_number' => $event->patient->medical_record_number,
                'timestamp' => now()->toIso8601String(),
            ]);
        } elseif ($event instanceof AppointmentScheduled) {
            $this->sentinelStackClient->sendMetric('appointment.scheduled', 1.0, [
                'appointment_id' => (string) $event->appointment->id,
                'patient_id' => (string) $event->appointment->patient_id,
                'clinician_id' => (string) $event->appointment->user_id,
                'appointment_date' => $event->appointment->appointment_date->toDateString(),
            ]);
        } elseif ($event instanceof AppointmentUpdated) {
            $this->sentinelStackClient->sendEvent('appointment.updated', [
                'appointment_id' => $event->appointment->id,
                'timestamp' => now()->toIso8601String(),
            ]);
        } elseif ($event instanceof AppointmentCancelled) {
            $this->sentinelStackClient->logIncident('appointment.cancelled', 'Appointment cancelled', [
                'appointment_id' => $event->appointment->id,
                'patient_id' => $event->appointment->patient_id,
                'cancellation_reason' => $event->appointment->cancellation_reason,
            ]);
        } elseif ($event instanceof RoomAssigned) {
            $this->sentinelStackClient->sendMetric('room.assigned', 1.0, [
                'appointment_id' => (string) $event->appointment->id,
                'room_id' => (string) $event->appointment->exam_room_id,
            ]);
        }
    }
}
