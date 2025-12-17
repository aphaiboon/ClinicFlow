<?php

use App\Services\Integration\NullSentinelStackClient;

it('implements SentinelStackClientInterface', function () {
    $client = new NullSentinelStackClient;

    expect($client)->toBeInstanceOf(\App\Services\Integration\SentinelStackClientInterface::class);
});

it('can send an event', function () {
    $client = new NullSentinelStackClient;
    $result = $client->sendEvent('test.event', ['data' => 'value']);
    expect($result)->toBeTrue();
});

it('can send a metric', function () {
    $client = new NullSentinelStackClient;
    $result = $client->sendMetric('test.metric', 123.45, ['tag' => 'value']);
    expect($result)->toBeTrue();
});

it('can log an incident', function () {
    $client = new NullSentinelStackClient;
    $result = $client->logIncident('test.incident', 'Something happened', ['error' => 'details']);
    expect($result)->toBeTrue();
});
