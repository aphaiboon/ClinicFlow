<?php

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(AuditService::class);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can log create action', function () {
    $auditLog = $this->service->logCreate('Patient', 123, ['name' => 'John Doe']);

    expect($auditLog)->toBeInstanceOf(AuditLog::class)
        ->and($auditLog->action)->toBe(AuditAction::Create)
        ->and($auditLog->resource_type)->toBe('Patient')
        ->and($auditLog->resource_id)->toBe(123)
        ->and($auditLog->user_id)->toBe($this->user->id)
        ->and($auditLog->changes)->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $this->user->id,
        'action' => AuditAction::Create->value,
        'resource_type' => 'Patient',
        'resource_id' => 123,
    ]);
});

it('can log update action with before and after states', function () {
    $before = ['status' => 'scheduled', 'exam_room_id' => null];
    $after = ['status' => 'scheduled', 'exam_room_id' => 5];

    $auditLog = $this->service->logUpdate('Appointment', 456, $before, $after);

    expect($auditLog->action)->toBe(AuditAction::Update)
        ->and($auditLog->changes)->toHaveKeys(['before', 'after'])
        ->and($auditLog->changes['before'])->toBe($before)
        ->and($auditLog->changes['after'])->toBe($after);
});

it('can log delete action', function () {
    $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

    $auditLog = $this->service->logDelete('Patient', 789, $data);

    expect($auditLog->action)->toBe(AuditAction::Delete)
        ->and($auditLog->changes)->toBeNull()
        ->and($auditLog->metadata)->toHaveKey('deleted_data')
        ->and($auditLog->metadata['deleted_data'])->toBe($data);
});

it('can log read action', function () {
    $auditLog = $this->service->logRead('Patient', 321);

    expect($auditLog->action)->toBe(AuditAction::Read)
        ->and($auditLog->resource_type)->toBe('Patient')
        ->and($auditLog->resource_id)->toBe(321)
        ->and($auditLog->changes)->toBeNull();
});

it('captures ip address when available', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100']);

    $auditLog = $this->service->logAction(
        AuditAction::Create,
        'Patient',
        123
    );

    expect($auditLog->ip_address)->not->toBeNull();
});

it('captures user agent when available', function () {
    $this->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Test Browser']);

    $auditLog = $this->service->logAction(
        AuditAction::Create,
        'Patient',
        123
    );

    expect($auditLog->user_agent)->not->toBeNull();
});

it('can include metadata', function () {
    $metadata = ['request_id' => 'req_123', 'endpoint' => '/api/patients'];

    $auditLog = $this->service->logAction(
        AuditAction::Create,
        'Patient',
        123,
        null,
        $metadata
    );

    expect($auditLog->metadata)->toBe($metadata);
});

it('creates immutable audit logs', function () {
    $auditLog = $this->service->logCreate('Patient', 123, []);

    expect(fn () => $auditLog->update(['action' => AuditAction::Update]))
        ->toThrow(\RuntimeException::class, 'Audit logs are immutable and cannot be updated');

    expect(fn () => $auditLog->delete())
        ->toThrow(\RuntimeException::class, 'Audit logs are immutable and cannot be deleted');
});

it('uses current authenticated user', function () {
    $otherUser = User::factory()->create();
    $this->actingAs($otherUser);

    $auditLog = $this->service->logCreate('Patient', 123, []);

    expect($auditLog->user_id)->toBe($otherUser->id);
});

it('can use generic logAction method', function () {
    $auditLog = $this->service->logAction(
        AuditAction::Create,
        'Patient',
        123
    );

    expect($auditLog->action)->toBe(AuditAction::Create)
        ->and($auditLog->resource_type)->toBe('Patient')
        ->and($auditLog->resource_id)->toBe(123);
});

it('handles changes parameter in logAction', function () {
    $changes = ['before' => ['a' => 1], 'after' => ['a' => 2]];

    $auditLog = $this->service->logAction(
        AuditAction::Update,
        'Patient',
        123,
        $changes
    );

    expect($auditLog->changes)->toBe($changes);
});
