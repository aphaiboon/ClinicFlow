<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\Log;

class NullSentinelStackClient implements SentinelStackClientInterface
{
    public function ingestEvent(array $envelope): bool
    {
        Log::info('NullSentinelStackClient: Event ingested', ['envelope' => $envelope]);

        return true;
    }

    public function ingestEvents(array $envelopes): bool
    {
        if (empty($envelopes)) {
            return true;
        }

        Log::info('NullSentinelStackClient: Batch events ingested', [
            'count' => count($envelopes),
            'envelopes' => $envelopes,
        ]);

        return true;
    }
}
