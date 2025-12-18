<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;

class EventEnvelopeBuilder
{
    public function __construct(
        private EventIdGenerator $eventIdGenerator
    ) {}

    public function buildEnvelope(string $eventType, array $payload = [], ?int $organizationId = null): array
    {
        return [
            'event_id' => $this->eventIdGenerator->generate(),
            'event_type' => $eventType,
            'timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'service' => [
                'service_id' => Config::get('sentinelstack.service_id', 'clinicflow'),
                'version' => Config::get('sentinelstack.service_version', '1.0.0'),
                'instance_id' => Config::get('sentinelstack.instance_id', 'unknown'),
                'region' => Config::get('sentinelstack.region', 'unknown'),
            ],
            'environment' => Config::get('sentinelstack.environment', 'development'),
            'tenant_id' => $organizationId ? (string) $organizationId : Config::get('sentinelstack.tenant_id'),
            'actor' => [
                'user_id' => Auth::id() ? (string) Auth::id() : null,
                'user_email' => Auth::user()?->email,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ],
            'correlation' => [
                'request_id' => Request::instance()->attributes->get('request_id'),
                'trace_id' => Request::instance()->attributes->get('trace_id'),
                'session_id' => Request::instance()->attributes->get('session_id'),
            ],
            'payload' => $payload,
        ];
    }
}
