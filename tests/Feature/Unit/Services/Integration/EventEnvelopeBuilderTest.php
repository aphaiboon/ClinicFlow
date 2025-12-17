<?php

use App\Services\Integration\EventEnvelopeBuilder;
use App\Services\Integration\EventIdGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

beforeEach(function () {
    Config::set('sentinelstack.service_id', 'clinicflow');
    Config::set('sentinelstack.service_version', '1.12.0');
    Config::set('sentinelstack.instance_id', 'app-server-01');
    Config::set('sentinelstack.region', 'us-west-2');
    Config::set('sentinelstack.environment', 'production');
    Config::set('sentinelstack.tenant_id', 'tenant_123');

    $this->eventIdGenerator = new EventIdGenerator;
    $this->envelopeBuilder = new EventEnvelopeBuilder($this->eventIdGenerator);
});

it('includes all required envelope fields', function () {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $request->attributes->set('request_id', 'req_123');
    $request->attributes->set('trace_id', 'trace_456');
    $request->attributes->set('session_id', 'sess_789');
    app()->instance('request', $request);

    $envelope = $this->envelopeBuilder->buildEnvelope('domain_event', ['test' => 'data']);

    expect($envelope)->toHaveKeys([
        'event_id',
        'event_type',
        'timestamp',
        'service',
        'environment',
        'tenant_id',
        'actor',
        'correlation',
        'payload',
    ]);
});

it('uses EventIdGenerator for event_id', function () {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $request->attributes->set('request_id', 'req_123');
    $request->attributes->set('trace_id', 'trace_456');
    app()->instance('request', $request);

    $envelope = $this->envelopeBuilder->buildEnvelope('domain_event', []);

    expect($envelope['event_id'])
        ->toBeString()
        ->toStartWith('evt_');
});

it('includes correct event_type', function () {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $request->attributes->set('request_id', 'req_123');
    $request->attributes->set('trace_id', 'trace_456');
    app()->instance('request', $request);

    $envelope = $this->envelopeBuilder->buildEnvelope('audit_log', []);

    expect($envelope['event_type'])->toBe('audit_log');
});

it('includes timestamp in ISO8601 UTC format', function () {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $request->attributes->set('request_id', 'req_123');
    $request->attributes->set('trace_id', 'trace_456');
    app()->instance('request', $request);

    $envelope = $this->envelopeBuilder->buildEnvelope('domain_event', []);

    expect($envelope['timestamp'])
        ->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/')
        ->and(date_create_from_format('Y-m-d\TH:i:s\Z', $envelope['timestamp']))->not->toBeFalse();
});

it('includes service info from configuration', function () {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $request->attributes->set('request_id', 'req_123');
    $request->attributes->set('trace_id', 'trace_456');
    app()->instance('request', $request);

    $envelope = $this->envelopeBuilder->buildEnvelope('domain_event', []);

    expect($envelope['service'])->toBe([
        'service_id' => 'clinicflow',
        'version' => '1.12.0',
        'instance_id' => 'app-server-01',
        'region' => 'us-west-2',
    ]);
});

it('includes actor context for authenticated user', function () {
    $user = \App\Models\User::factory()->create(['email' => 'user@example.com']);

    Auth::login($user);

    $request = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], [
        'REMOTE_ADDR' => '192.168.1.100',
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
    ]);
    $request->attributes->set('request_id', 'req_123');
    $request->attributes->set('trace_id', 'trace_456');
    app()->instance('request', $request);

    $envelope = $this->envelopeBuilder->buildEnvelope('domain_event', []);

    expect($envelope['actor'])->toBe([
        'user_id' => (string) $user->id,
        'user_email' => 'user@example.com',
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0',
    ]);
});

it('handles unauthenticated requests gracefully', function () {
    Auth::logout();

    $request = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], [
        'REMOTE_ADDR' => '192.168.1.100',
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
    ]);
    $request->attributes->set('request_id', 'req_123');
    $request->attributes->set('trace_id', 'trace_456');
    app()->instance('request', $request);

    $envelope = $this->envelopeBuilder->buildEnvelope('domain_event', []);

    expect($envelope['actor'])->toBe([
        'user_id' => null,
        'user_email' => null,
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0',
    ]);
});

it('includes correlation IDs from request context middleware', function () {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $request->attributes->set('request_id', 'req_123');
    $request->attributes->set('trace_id', 'trace_456');
    $request->attributes->set('session_id', 'sess_789');
    app()->instance('request', $request);

    $envelope = $this->envelopeBuilder->buildEnvelope('domain_event', []);

    expect($envelope['correlation'])->toBe([
        'request_id' => 'req_123',
        'trace_id' => 'trace_456',
        'session_id' => 'sess_789',
    ]);
});

it('handles missing correlation IDs gracefully', function () {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    app()->instance('request', $request);

    $envelope = $this->envelopeBuilder->buildEnvelope('domain_event', []);

    expect($envelope['correlation'])->toBe([
        'request_id' => null,
        'trace_id' => null,
        'session_id' => null,
    ]);
});

it('correctly nests payload', function () {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $request->attributes->set('request_id', 'req_123');
    $request->attributes->set('trace_id', 'trace_456');
    app()->instance('request', $request);

    $payload = ['patient_id' => 123, 'action' => 'created'];

    $envelope = $this->envelopeBuilder->buildEnvelope('domain_event', $payload);

    expect($envelope['payload'])->toBe($payload);
});

it('includes environment from configuration', function () {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $request->attributes->set('request_id', 'req_123');
    $request->attributes->set('trace_id', 'trace_456');
    app()->instance('request', $request);

    $envelope = $this->envelopeBuilder->buildEnvelope('domain_event', []);

    expect($envelope['environment'])->toBe('production');
});

it('includes tenant_id from configuration', function () {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $request->attributes->set('request_id', 'req_123');
    $request->attributes->set('trace_id', 'trace_456');
    app()->instance('request', $request);

    $envelope = $this->envelopeBuilder->buildEnvelope('domain_event', []);

    expect($envelope['tenant_id'])->toBe('tenant_123');
});
