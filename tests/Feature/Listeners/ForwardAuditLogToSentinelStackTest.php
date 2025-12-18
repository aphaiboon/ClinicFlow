<?php

use App\Enums\AuditAction;
use App\Events\AuditLogCreated;
use App\Listeners\ForwardAuditLogToSentinelStack;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Services\Integration\EventEnvelopeBuilder;
use App\Services\Integration\SentinelStackClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

use function Pest\Laravel\mock;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->envelopeBuilder = mock(EventEnvelopeBuilder::class);
    $this->client = mock(SentinelStackClientInterface::class);
    $this->listener = new ForwardAuditLogToSentinelStack($this->client, $this->envelopeBuilder);

    $request = Request::create('/test', 'GET');
    $request->attributes->set('request_id', 'req_123');
    $request->attributes->set('trace_id', 'trace_456');
    app()->instance('request', $request);
});

it('forwards audit log to SentinelStack as audit_log event type', function () {
    $user = User::factory()->create();
    $auditLog = AuditLog::factory()->for($this->organization)->create([
        'user_id' => $user->id,
        'action' => AuditAction::Create,
        'resource_type' => 'Patient',
        'resource_id' => 123,
    ]);
    $event = new AuditLogCreated($auditLog);

    $expectedEnvelope = [
        'event_type' => 'audit_log',
        'payload' => [
            'audit_log_id' => $auditLog->id,
            'action' => 'create',
            'resource_type' => 'Patient',
            'resource_id' => 123,
        ],
    ];

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('audit_log', \Mockery::on(function ($payload) use ($auditLog) {
            return $payload['audit_log_id'] === $auditLog->id
                && $payload['action'] === 'create'
                && $payload['resource_type'] === 'Patient'
                && $payload['resource_id'] === 123;
        }), $this->organization->id)
        ->andReturn($expectedEnvelope);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->with($expectedEnvelope)
        ->andReturn(true);

    $this->listener->handle($event);
});

it('includes compliance-ready format (who, what, when, where, why)', function () {
    $user = User::factory()->create(['email' => 'user@example.com']);
    $auditLog = AuditLog::factory()->for($this->organization)->create([
        'user_id' => $user->id,
        'action' => AuditAction::Update,
        'resource_type' => 'Appointment',
        'resource_id' => 456,
        'ip_address' => '192.168.1.100',
        'user_agent' => 'Mozilla/5.0',
        'changes' => ['before' => ['status' => 'scheduled'], 'after' => ['status' => 'completed']],
    ]);
    $event = new AuditLogCreated($auditLog);

    $capturedPayload = null;

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('audit_log', \Mockery::capture($capturedPayload), $this->organization->id)
        ->andReturn(['event_type' => 'audit_log', 'payload' => []]);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->andReturn(true);

    $this->listener->handle($event);

    expect($capturedPayload)
        ->toHaveKey('who')
        ->toHaveKey('what')
        ->toHaveKey('when')
        ->toHaveKey('where')
        ->toHaveKey('why')
        ->and($capturedPayload['audit_log_id'])->toBe($auditLog->id)
        ->and($capturedPayload['action'])->toBe('update')
        ->and($capturedPayload['resource_type'])->toBe('Appointment')
        ->and($capturedPayload['resource_id'])->toBe(456);
});

it('handles audit log without changes', function () {
    $auditLog = AuditLog::factory()->for($this->organization)->create([
        'action' => AuditAction::Read,
        'changes' => null,
    ]);
    $event = new AuditLogCreated($auditLog);

    $this->envelopeBuilder->shouldReceive('buildEnvelope')
        ->once()
        ->with('audit_log', \Mockery::type('array'), $this->organization->id)
        ->andReturn(['event_type' => 'audit_log', 'payload' => []]);

    $this->client->shouldReceive('ingestEvent')
        ->once()
        ->andReturn(true);

    $this->listener->handle($event);
});
