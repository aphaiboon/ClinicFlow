<?php

namespace App\Services\Integration;

class NullSentinelStackClient implements SentinelStackClientInterface
{
    public function forwardMetric(string $metric, array $data): void {}

    public function forwardIncident(string $incident, array $data): void {}

    public function forwardAuditLog(array $auditData): void {}
}
