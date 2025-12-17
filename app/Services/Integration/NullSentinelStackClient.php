<?php

namespace App\Services\Integration;

class NullSentinelStackClient implements SentinelStackClientInterface
{
    public function sendEvent(string $eventType, array $payload): bool
    {
        \Log::info("NullSentinelStackClient: Event {$eventType} sent with payload: ".json_encode($payload));

        return true;
    }

    public function sendMetric(string $metricName, float $value, array $tags = []): bool
    {
        \Log::info("NullSentinelStackClient: Metric {$metricName} with value {$value} and tags: ".json_encode($tags));

        return true;
    }

    public function logIncident(string $incidentType, string $message, array $details = []): bool
    {
        \Log::warning("NullSentinelStackClient: Incident {$incidentType} logged with message: {$message} and details: ".json_encode($details));

        return true;
    }
}
