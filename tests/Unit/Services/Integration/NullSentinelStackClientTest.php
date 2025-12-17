<?php

use App\Services\Integration\NullSentinelStackClient;

it('implements SentinelStackClientInterface', function () {
    $client = new NullSentinelStackClient();

    expect($client)->toBeInstanceOf(\App\Services\Integration\SentinelStackClientInterface::class);
});

it('does not throw exceptions when forwarding metrics', function () {
    $client = new NullSentinelStackClient();

    expect(fn () => $client->forwardMetric('test.metric', ['value' => 1]))
        ->not->toThrow(\Throwable::class);
});

it('does not throw exceptions when forwarding incidents', function () {
    $client = new NullSentinelStackClient();

    expect(fn () => $client->forwardIncident('test.incident', ['message' => 'test']))
        ->not->toThrow(\Throwable::class);
});

it('does not throw exceptions when forwarding audit logs', function () {
    $client = new NullSentinelStackClient();

    expect(fn () => $client->forwardAuditLog(['action' => 'create', 'resource' => 'test']))
        ->not->toThrow(\Throwable::class);
});

