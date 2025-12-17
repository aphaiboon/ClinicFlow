<?php

namespace App\Events;

use App\Models\AuditLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditLogCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AuditLog $auditLog
    ) {}
}
