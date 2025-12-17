<?php

namespace App\Services\Integration;

interface SentinelStackClientInterface
{
    public function forwardMetric(string $metric, array $data): void;

    public function forwardIncident(string $incident, array $data): void;

    public function forwardAuditLog(array $auditData): void;
}
