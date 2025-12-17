<?php

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->receptionist = User::factory()->create(['role' => UserRole::Receptionist]);
    $this->clinician = User::factory()->create(['role' => UserRole::Clinician]);
});

it('requires authentication to view audit logs index', function () {
    $response = $this->get('/audit-logs');

    $response->assertRedirect(route('login'));
});

it('displays audit logs index for admin', function () {
    AuditLog::factory()->count(5)->create();

    $response = $this->actingAs($this->admin)->get('/audit-logs');

    $response->assertSuccessful();
});

it('prevents non-admin from viewing audit logs index', function () {
    AuditLog::factory()->count(3)->create();

    $response = $this->actingAs($this->receptionist)->get('/audit-logs');

    $response->assertForbidden();
});

it('prevents clinician from viewing audit logs index', function () {
    AuditLog::factory()->count(3)->create();

    $response = $this->actingAs($this->clinician)->get('/audit-logs');

    $response->assertForbidden();
});

it('displays audit log details for admin', function () {
    $auditLog = AuditLog::factory()->create();

    $response = $this->actingAs($this->admin)->get("/audit-logs/{$auditLog->id}");

    $response->assertSuccessful();
});

it('prevents non-admin from viewing audit log details', function () {
    $auditLog = AuditLog::factory()->create();

    $response = $this->actingAs($this->receptionist)->get("/audit-logs/{$auditLog->id}");

    $response->assertForbidden();
});

it('can filter audit logs by user', function () {
    $user = User::factory()->create();
    AuditLog::factory()->create(['user_id' => $user->id]);
    AuditLog::factory()->count(2)->create();

    $response = $this->actingAs($this->admin)->get("/audit-logs?user_id={$user->id}");

    $response->assertSuccessful();
});

it('can filter audit logs by resource type', function () {
    AuditLog::factory()->create(['resource_type' => 'Patient']);
    AuditLog::factory()->create(['resource_type' => 'Appointment']);

    $response = $this->actingAs($this->admin)->get('/audit-logs?resource_type=Patient');

    $response->assertSuccessful();
});

it('can filter audit logs by action', function () {
    AuditLog::factory()->create(['action' => \App\Enums\AuditAction::Create]);
    AuditLog::factory()->create(['action' => \App\Enums\AuditAction::Update]);

    $response = $this->actingAs($this->admin)->get('/audit-logs?action=create');

    $response->assertSuccessful();
});
