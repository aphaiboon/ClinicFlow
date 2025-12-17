<?php

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new AuditLogPolicy;
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->clinician = User::factory()->create(['role' => UserRole::Clinician]);
    $this->receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
});

it('allows admin to view any audit log', function () {
    $auditLog = AuditLog::factory()->create();

    expect($this->policy->view($this->admin, $auditLog))->toBeTrue();
});

it('prevents clinician from viewing audit logs', function () {
    $auditLog = AuditLog::factory()->create();

    expect($this->policy->view($this->clinician, $auditLog))->toBeFalse();
});

it('prevents receptionist from viewing audit logs', function () {
    $auditLog = AuditLog::factory()->create();

    expect($this->policy->view($this->receptionist, $auditLog))->toBeFalse();
});
