<?php

namespace App\Services\Integration;

interface SentinelStackClientInterface
{
    public function ingestEvent(array $envelope): bool;

    public function ingestEvents(array $envelopes): bool;
}
