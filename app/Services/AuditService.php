<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Events\AuditLogCreated;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function logAction(
        AuditAction $action,
        string $resourceType,
        int $resourceId,
        ?array $changes = null,
        ?array $metadata = null
    ): AuditLog {
        // Check both web and patient guards explicitly
        // Use guard() to ensure we're checking the correct guard
        $user = Auth::guard('web')->user();
        $patient = Auth::guard('patient')->user();

        // If patient is authenticated, user_id must be null
        // Explicitly set to null to prevent foreign key constraint violations
        if ($patient) {
            $userId = null;
        } else {
            $userId = $user?->id;
        }

        $auditData = [
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'changes' => $changes,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => $metadata,
        ];

        // Set organization_id from user or patient
        if ($user && $user->current_organization_id) {
            $auditData['organization_id'] = $user->current_organization_id;
        } elseif ($patient && $patient->organization_id) {
            $auditData['organization_id'] = $patient->organization_id;
        }

        // Add patient info to metadata if action was performed by patient
        if ($patient) {
            $auditData['metadata'] = array_merge($auditData['metadata'] ?? [], [
                'patient_id' => $patient->id,
                'performed_by' => 'patient',
            ]);
        }

        $auditLog = AuditLog::create($auditData);

        event(new AuditLogCreated($auditLog));

        return $auditLog;
    }

    public function logCreate(string $resourceType, int $resourceId, array $data): AuditLog
    {
        return $this->logAction(AuditAction::Create, $resourceType, $resourceId);
    }

    public function logUpdate(
        string $resourceType,
        int $resourceId,
        array $before,
        array $after
    ): AuditLog {
        return $this->logAction(
            AuditAction::Update,
            $resourceType,
            $resourceId,
            ['before' => $before, 'after' => $after]
        );
    }

    public function logDelete(string $resourceType, int $resourceId, array $data): AuditLog
    {
        return $this->logAction(
            AuditAction::Delete,
            $resourceType,
            $resourceId,
            null,
            ['deleted_data' => $data]
        );
    }

    public function logRead(string $resourceType, int $resourceId): AuditLog
    {
        return $this->logAction(AuditAction::Read, $resourceType, $resourceId);
    }
}
