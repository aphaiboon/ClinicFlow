<?php

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ExamRoom;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = \App\Models\Organization::factory()->create();
});

it('enforces authorization for different user roles accessing patients', function () {
    $admin = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $receptionist = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $clinician = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($admin->id, ['role' => \App\Enums\OrganizationRole::Admin->value, 'joined_at' => now()]);
    $this->organization->users()->attach($receptionist->id, ['role' => \App\Enums\OrganizationRole::Receptionist->value, 'joined_at' => now()]);
    $this->organization->users()->attach($clinician->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $patient = Patient::factory()->for($this->organization)->create();

    $this->actingAs($admin)->get('/patients')->assertSuccessful();
    $this->actingAs($receptionist)->get('/patients')->assertSuccessful();
    $this->actingAs($clinician)->get('/patients')->assertSuccessful();

    $this->actingAs($admin)->get("/patients/{$patient->id}")->assertSuccessful();
    $this->actingAs($receptionist)->get("/patients/{$patient->id}")->assertSuccessful();
    $this->actingAs($clinician)->get("/patients/{$patient->id}")->assertSuccessful();

    $this->actingAs($admin)->get('/patients/create')->assertSuccessful();
    $this->actingAs($receptionist)->get('/patients/create')->assertSuccessful();
    $this->actingAs($clinician)->get('/patients/create')->assertForbidden();
});

it('enforces authorization for different user roles accessing appointments', function () {
    $admin = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $receptionist = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $clinician = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($admin->id, ['role' => \App\Enums\OrganizationRole::Admin->value, 'joined_at' => now()]);
    $this->organization->users()->attach($receptionist->id, ['role' => \App\Enums\OrganizationRole::Receptionist->value, 'joined_at' => now()]);
    $this->organization->users()->attach($clinician->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $appointment = Appointment::factory()->for($this->organization)->create(['user_id' => $clinician->id]);

    $this->actingAs($admin)->get('/appointments')->assertSuccessful();
    $this->actingAs($receptionist)->get('/appointments')->assertSuccessful();
    $this->actingAs($clinician)->get('/appointments')->assertSuccessful();

    $this->actingAs($admin)->get("/appointments/{$appointment->id}")->assertSuccessful();
    $this->actingAs($receptionist)->get("/appointments/{$appointment->id}")->assertSuccessful();
    $this->actingAs($clinician)->get("/appointments/{$appointment->id}")->assertSuccessful();

    $this->actingAs($admin)->get('/appointments/create')->assertSuccessful();
    $this->actingAs($receptionist)->get('/appointments/create')->assertSuccessful();
    $this->actingAs($clinician)->get('/appointments/create')->assertForbidden();
});

it('enforces authorization for appointment cancellation by role', function () {
    $receptionist = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $clinician = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($receptionist->id, ['role' => \App\Enums\OrganizationRole::Receptionist->value, 'joined_at' => now()]);
    $this->organization->users()->attach($clinician->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $appointment = Appointment::factory()->for($this->organization)->create(['status' => \App\Enums\AppointmentStatus::Scheduled]);

    $this->actingAs($receptionist)
        ->post("/appointments/{$appointment->id}/cancel", ['reason' => 'Test'])
        ->assertRedirect();

    $appointment2 = Appointment::factory()->for($this->organization)->create(['status' => \App\Enums\AppointmentStatus::Scheduled]);

    $this->actingAs($clinician)
        ->post("/appointments/{$appointment2->id}/cancel", ['reason' => 'Test'])
        ->assertForbidden();
});

it('enforces admin-only access to audit logs', function () {
    $admin = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $receptionist = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $clinician = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($admin->id, ['role' => \App\Enums\OrganizationRole::Admin->value, 'joined_at' => now()]);
    $this->organization->users()->attach($receptionist->id, ['role' => \App\Enums\OrganizationRole::Receptionist->value, 'joined_at' => now()]);
    $this->organization->users()->attach($clinician->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $auditLog = AuditLog::factory()->for($this->organization)->create();

    $this->actingAs($admin)->get('/audit-logs')->assertSuccessful();
    $this->actingAs($receptionist)->get('/audit-logs')->assertForbidden();
    $this->actingAs($clinician)->get('/audit-logs')->assertForbidden();

    $this->actingAs($admin)->get("/audit-logs/{$auditLog->id}")->assertSuccessful();
    $this->actingAs($receptionist)->get("/audit-logs/{$auditLog->id}")->assertForbidden();
    $this->actingAs($clinician)->get("/audit-logs/{$auditLog->id}")->assertForbidden();
});

it('enforces authorization for exam room management', function () {
    $admin = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $receptionist = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $clinician = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($admin->id, ['role' => \App\Enums\OrganizationRole::Admin->value, 'joined_at' => now()]);
    $this->organization->users()->attach($receptionist->id, ['role' => \App\Enums\OrganizationRole::Receptionist->value, 'joined_at' => now()]);
    $this->organization->users()->attach($clinician->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $room = ExamRoom::factory()->for($this->organization)->create();

    $this->actingAs($admin)->get('/exam-rooms')->assertSuccessful();
    $this->actingAs($receptionist)->get('/exam-rooms')->assertSuccessful();
    $this->actingAs($clinician)->get('/exam-rooms')->assertSuccessful();

    $this->actingAs($admin)->get('/exam-rooms/create')->assertSuccessful();
    $this->actingAs($receptionist)->get('/exam-rooms/create')->assertForbidden();
    $this->actingAs($clinician)->get('/exam-rooms/create')->assertForbidden();
});
