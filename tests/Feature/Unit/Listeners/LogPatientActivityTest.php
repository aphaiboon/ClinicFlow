<?php

use App\Enums\AuditAction;
use App\Events\PatientCreated;
use App\Events\PatientUpdated;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->auditService = new AuditService;
});

it('logs patient creation when PatientCreated event is fired', function () {
    $patient = Patient::factory()->make();
    $patient->save();

    $listener = new \App\Listeners\LogPatientActivity($this->auditService);
    $event = new PatientCreated($patient->fresh());
    $listener->handle($event);

    $auditLog = AuditLog::where('resource_type', 'Patient')
        ->where('resource_id', $patient->id)
        ->where('action', AuditAction::Create)
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->user_id)->toBe($this->user->id)
        ->and($auditLog->resource_type)->toBe('Patient')
        ->and($auditLog->resource_id)->toBe($patient->id);
});

it('logs patient update when PatientUpdated event is fired', function () {
    $patient = Patient::factory()->create(['first_name' => 'John']);

    $patient->first_name = 'Jane';
    $patient->save();

    $listener = new \App\Listeners\LogPatientActivity($this->auditService);
    $event = new PatientUpdated($patient->fresh());
    $listener->handle($event);

    $auditLog = AuditLog::where('resource_type', 'Patient')
        ->where('resource_id', $patient->id)
        ->where('action', AuditAction::Update)
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->user_id)->toBe($this->user->id)
        ->and($auditLog->changes)->toBeArray();
});
