<?php

namespace App\Listeners;

use App\Events\AppointmentCancelled;
use App\Events\AppointmentScheduled;
use App\Events\AppointmentUpdated;
use App\Events\RoomAssigned;
use App\Services\AuditService;

class LogAppointmentActivity
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function handle(AppointmentScheduled|AppointmentUpdated|AppointmentCancelled|RoomAssigned $event): void
    {
        if ($event instanceof AppointmentScheduled) {
            $this->auditService->logCreate(
                'Appointment',
                $event->appointment->id,
                $event->appointment->toArray()
            );
        } elseif ($event instanceof AppointmentUpdated) {
            $this->auditService->logUpdate(
                'Appointment',
                $event->appointment->id,
                $event->appointment->getOriginal(),
                $event->appointment->getChanges()
            );
        } elseif ($event instanceof AppointmentCancelled) {
            $this->auditService->logUpdate(
                'Appointment',
                $event->appointment->id,
                $event->appointment->getOriginal(),
                ['status' => 'cancelled', 'cancellation_reason' => $event->appointment->cancellation_reason]
            );
        } elseif ($event instanceof RoomAssigned) {
            $this->auditService->logUpdate(
                'Appointment',
                $event->appointment->id,
                $event->appointment->getOriginal(),
                ['exam_room_id' => $event->appointment->exam_room_id]
            );
        }
    }
}
