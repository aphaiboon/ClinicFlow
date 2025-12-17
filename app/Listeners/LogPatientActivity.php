<?php

namespace App\Listeners;

use App\Events\PatientCreated;
use App\Events\PatientUpdated;
use App\Services\AuditService;

class LogPatientActivity
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function handle(PatientCreated|PatientUpdated $event): void
    {
        if ($event instanceof PatientCreated) {
            $this->auditService->logCreate(
                'Patient',
                $event->patient->id,
                $event->patient->toArray()
            );
        } elseif ($event instanceof PatientUpdated) {
            $this->auditService->logUpdate(
                'Patient',
                $event->patient->id,
                $event->patient->getOriginal(),
                $event->patient->getChanges()
            );
        }
    }
}
