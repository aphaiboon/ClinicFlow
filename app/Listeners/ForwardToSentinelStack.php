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

    public function handlePatientCreated(PatientCreated $event): void
    {
        $this->sentinelStackClient->forwardAuditLog([
            'event' => 'patient.created',
            'patient_id' => $event->patient->id,
            'medical_record_number' => $event->patient->medical_record_number,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function handlePatientUpdated(PatientUpdated $event): void
    {
        $this->sentinelStackClient->forwardAuditLog([
            'event' => 'patient.updated',
            'patient_id' => $event->patient->id,
            'medical_record_number' => $event->patient->medical_record_number,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function handleAppointmentScheduled(AppointmentScheduled $event): void
    {
        $this->sentinelStackClient->forwardMetric('appointment.scheduled', [
            'appointment_id' => $event->appointment->id,
            'patient_id' => $event->appointment->patient_id,
            'clinician_id' => $event->appointment->user_id,
            'appointment_date' => $event->appointment->appointment_date->toDateString(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function handleAppointmentUpdated(AppointmentUpdated $event): void
    {
        $this->sentinelStackClient->forwardAuditLog([
            'event' => 'appointment.updated',
            'appointment_id' => $event->appointment->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function handleAppointmentCancelled(AppointmentCancelled $event): void
    {
        $this->sentinelStackClient->forwardIncident('appointment.cancelled', [
            'appointment_id' => $event->appointment->id,
            'patient_id' => $event->appointment->patient_id,
            'cancellation_reason' => $event->appointment->cancellation_reason,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function handleRoomAssigned(RoomAssigned $event): void
    {
        $this->sentinelStackClient->forwardMetric('room.assigned', [
            'appointment_id' => $event->appointment->id,
            'room_id' => $event->appointment->exam_room_id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
