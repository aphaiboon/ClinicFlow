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
        $auditLog = AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'changes' => $changes,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => $metadata,
        ]);

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
