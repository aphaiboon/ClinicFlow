<?php

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = \App\Models\Organization::factory()->create();
    $this->admin = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->receptionist = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->clinician = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->admin->id, ['role' => \App\Enums\OrganizationRole::Admin->value, 'joined_at' => now()]);
    $this->organization->users()->attach($this->receptionist->id, ['role' => \App\Enums\OrganizationRole::Receptionist->value, 'joined_at' => now()]);
    $this->organization->users()->attach($this->clinician->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
});

it('requires authentication to view audit logs index', function () {
    $response = $this->get('/audit-logs');

    $response->assertRedirect(route('login'));
});

it('displays audit logs index for admin', function () {
    AuditLog::factory()->for($this->organization)->count(5)->create();

    $response = $this->actingAs($this->admin)->get('/audit-logs');

    $response->assertSuccessful();
});

it('prevents non-admin from viewing audit logs index', function () {
    AuditLog::factory()->for($this->organization)->count(3)->create();

    $response = $this->actingAs($this->receptionist)->get('/audit-logs');

    $response->assertForbidden();
});

it('prevents clinician from viewing audit logs index', function () {
    AuditLog::factory()->for($this->organization)->count(3)->create();

    $response = $this->actingAs($this->clinician)->get('/audit-logs');

    $response->assertForbidden();
});

it('displays audit log details for admin', function () {
    $auditLog = AuditLog::factory()->for($this->organization)->create();

    $response = $this->actingAs($this->admin)->get("/audit-logs/{$auditLog->id}");

    $response->assertSuccessful();
});

it('prevents non-admin from viewing audit log details', function () {
    $auditLog = AuditLog::factory()->for($this->organization)->create();

    $response = $this->actingAs($this->receptionist)->get("/audit-logs/{$auditLog->id}");

    $response->assertForbidden();
});

it('can filter audit logs by user', function () {
    $user = User::factory()->create(['current_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($user->id, ['role' => \App\Enums\OrganizationRole::Receptionist->value, 'joined_at' => now()]);
    AuditLog::factory()->for($this->organization)->create(['user_id' => $user->id]);
    AuditLog::factory()->for($this->organization)->count(2)->create();

    $response = $this->actingAs($this->admin)->get("/audit-logs?user_id={$user->id}");

    $response->assertSuccessful();
});

it('can filter audit logs by resource type', function () {
    AuditLog::factory()->for($this->organization)->create(['resource_type' => 'Patient']);
    AuditLog::factory()->for($this->organization)->create(['resource_type' => 'Appointment']);

    $response = $this->actingAs($this->admin)->get('/audit-logs?resource_type=Patient');

    $response->assertSuccessful();
});

it('can filter audit logs by action', function () {
    AuditLog::factory()->for($this->organization)->create(['action' => \App\Enums\AuditAction::Create]);
    AuditLog::factory()->for($this->organization)->create(['action' => \App\Enums\AuditAction::Update]);

    $response = $this->actingAs($this->admin)->get('/audit-logs?action=create');

    $response->assertSuccessful();
});
