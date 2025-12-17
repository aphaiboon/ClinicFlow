<?php

use App\Services\Integration\NullSentinelStackClient;

beforeEach(function () {
    $this->client = new NullSentinelStackClient();
});

it('ingests single event successfully', function () {
    $envelope = [
        'event_id' => 'evt_123',
        'event_type' => 'domain_event',
        'timestamp' => now()->toIso8601String(),
        'payload' => ['test' => 'data'],
    ];

    $result = $this->client->ingestEvent($envelope);

    expect($result)->toBeTrue();
});

it('ingests batch events successfully', function () {
    $envelopes = [
        [
            'event_id' => 'evt_123',
            'event_type' => 'domain_event',
            'timestamp' => now()->toIso8601String(),
            'payload' => ['test' => 'data1'],
        ],
        [
            'event_id' => 'evt_456',
            'event_type' => 'audit_log',
            'timestamp' => now()->toIso8601String(),
            'payload' => ['test' => 'data2'],
        ],
    ];

    $result = $this->client->ingestEvents($envelopes);

    expect($result)->toBeTrue();
});

it('handles empty batch gracefully', function () {
    $result = $this->client->ingestEvents([]);

    expect($result)->toBeTrue();
});

it('handles invalid envelope gracefully', function () {
    $invalidEnvelope = ['invalid' => 'data'];

    $result = $this->client->ingestEvent($invalidEnvelope);

    expect($result)->toBeTrue();
});

it('implements SentinelStackClientInterface', function () {
    expect($this->client)->toBeInstanceOf(\App\Services\Integration\SentinelStackClientInterface::class);
});
