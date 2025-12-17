<?php

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;

it('has fillable attributes', function () {
    $auditLog = new AuditLog;
    $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'changes',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    expect($auditLog->getFillable())->toBe($fillable);
});

it('does not have timestamps', function () {
    $auditLog = new AuditLog;

    expect($auditLog->timestamps)->toBeFalse();
});

it('casts action to enum', function () {
    $auditLog = AuditLog::factory()->create(['action' => AuditAction::Create]);

    expect($auditLog->action)->toBe(AuditAction::Create);
});

it('casts changes to array', function () {
    $changes = ['before' => ['status' => 'scheduled'], 'after' => ['status' => 'completed']];
    $auditLog = AuditLog::factory()->create(['changes' => $changes]);

    expect($auditLog->changes)->toBe($changes)
        ->and($auditLog->changes)->toBeArray();
});

it('casts metadata to array', function () {
    $metadata = ['request_id' => 'req_123', 'endpoint' => '/api/appointments'];
    $auditLog = AuditLog::factory()->create(['metadata' => $metadata]);

    expect($auditLog->metadata)->toBe($metadata)
        ->and($auditLog->metadata)->toBeArray();
});

it('automatically sets created_at on creation', function () {
    $auditLog = AuditLog::factory()->create();

    expect($auditLog->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('has user relationship', function () {
    $user = User::factory()->create();
    $auditLog = AuditLog::factory()->create(['user_id' => $user->id]);

    expect($auditLog->user)->toBeInstanceOf(User::class)
        ->and($auditLog->user->id)->toBe($user->id);
});

it('can scope by user', function () {
    $user = User::factory()->create();
    AuditLog::factory()->count(2)->create(['user_id' => $user->id]);
    AuditLog::factory()->create();

    $userLogs = AuditLog::byUser($user->id)->get();

    expect($userLogs)->toHaveCount(2);
});

it('can scope by resource', function () {
    AuditLog::factory()->create(['resource_type' => 'Patient', 'resource_id' => 1]);
    AuditLog::factory()->create(['resource_type' => 'Patient', 'resource_id' => 2]);
    AuditLog::factory()->create(['resource_type' => 'Appointment', 'resource_id' => 1]);

    $patientLogs = AuditLog::byResource('Patient', 1)->get();

    expect($patientLogs)->toHaveCount(1);
});

it('can scope by action', function () {
    AuditLog::factory()->count(2)->create(['action' => AuditAction::Create]);
    AuditLog::factory()->create(['action' => AuditAction::Update]);

    $createLogs = AuditLog::byAction(AuditAction::Create)->get();

    expect($createLogs)->toHaveCount(2);
});

it('can scope by date range', function () {
    $start = Carbon::now()->subDays(10);
    $end = Carbon::now()->addDays(10);

    AuditLog::factory()->create(['created_at' => $start->copy()->subDays(5)]);
    AuditLog::factory()->create(['created_at' => $start->copy()->addDays(2)]);
    AuditLog::factory()->create(['created_at' => $end->copy()->addDays(5)]);

    $inRange = AuditLog::byDateRange($start, $end)->get();

    expect($inRange)->toHaveCount(1);
});
