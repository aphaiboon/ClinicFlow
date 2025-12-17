<?php

namespace App\Services\Integration;

interface SentinelStackClientInterface
{
    public function sendEvent(string $eventType, array $payload): bool;

    public function sendMetric(string $metricName, float $value, array $tags = []): bool;

    public function logIncident(string $incidentType, string $message, array $details = []): bool;
}
