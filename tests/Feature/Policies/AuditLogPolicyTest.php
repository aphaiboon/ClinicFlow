<?php

use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->policy = new AuditLogPolicy;
    $this->admin = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->clinician = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->receptionist = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->organization->users()->attach($this->admin->id, [
        'role' => OrganizationRole::Admin->value,
        'joined_at' => now(),
    ]);
    $this->organization->users()->attach($this->clinician->id, [
        'role' => OrganizationRole::Clinician->value,
        'joined_at' => now(),
    ]);
    $this->organization->users()->attach($this->receptionist->id, [
        'role' => OrganizationRole::Receptionist->value,
        'joined_at' => now(),
    ]);
});

it('allows admin to view any audit log', function () {
    $auditLog = AuditLog::factory()->for($this->organization)->create();

    expect($this->policy->view($this->admin, $auditLog))->toBeTrue();
});

it('prevents clinician from viewing audit logs', function () {
    $auditLog = AuditLog::factory()->for($this->organization)->create();

    expect($this->policy->view($this->clinician, $auditLog))->toBeFalse();
});

it('prevents receptionist from viewing audit logs', function () {
    $auditLog = AuditLog::factory()->for($this->organization)->create();

    expect($this->policy->view($this->receptionist, $auditLog))->toBeFalse();
});
