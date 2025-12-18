<?php

namespace App\Listeners;

use App\Events\AuditLogCreated;
use App\Services\Integration\EventEnvelopeBuilder;
use App\Services\Integration\SentinelStackClientInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ForwardAuditLogToSentinelStack implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected SentinelStackClientInterface $sentinelStackClient,
        protected EventEnvelopeBuilder $envelopeBuilder
    ) {}

    public function handle(AuditLogCreated $event): void
    {
        $auditLog = $event->auditLog;

        $payload = [
            'audit_log_id' => $auditLog->id,
            'action' => $auditLog->action->value,
            'resource_type' => $auditLog->resource_type,
            'resource_id' => $auditLog->resource_id,
            'who' => [
                'user_id' => $auditLog->user_id,
                'user_email' => $auditLog->user?->email,
            ],
            'what' => [
                'action' => $auditLog->action->value,
                'resource_type' => $auditLog->resource_type,
                'resource_id' => $auditLog->resource_id,
            ],
            'when' => $auditLog->created_at?->toIso8601String(),
            'where' => [
                'ip_address' => $auditLog->ip_address,
                'user_agent' => $auditLog->user_agent,
            ],
            'why' => $auditLog->metadata,
            'changes' => $auditLog->changes,
        ];

        $organizationId = $event->auditLog->organization_id;
        $envelope = $this->envelopeBuilder->buildEnvelope('audit_log', $payload, $organizationId);

        $this->sentinelStackClient->ingestEvent($envelope);
    }
}
