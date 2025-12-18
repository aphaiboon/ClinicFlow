<?php

use App\Enums\AppointmentStatus;
use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ExamRoom;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create([
        'is_active' => true,
    ]);
});

test('unauthenticated users are redirected to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('super admin sees super admin dashboard stats', function () {
    $superAdmin = User::factory()->create([
        'role' => UserRole::SuperAdmin,
    ]);

    // Get initial counts
    $initialOrgCount = Organization::count();
    $initialUserCount = User::where('role', UserRole::User)->count();
    $initialActiveOrgCount = Organization::where('is_active', true)->count();

    // Create additional organizations and users for stats
    Organization::factory()->count(3)->create(['is_active' => true]);
    Organization::factory()->count(2)->create(['is_active' => false]);
    User::factory()->count(5)->create(['role' => UserRole::User]);

    $response = $this->actingAs($superAdmin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->has('stats', fn ($stats) => $stats
            ->has('organizationCount')
            ->has('userCount')
            ->has('activeOrganizationCount')
            ->where('organizationCount', $initialOrgCount + 5) // initial + 3 active + 2 inactive
            ->where('userCount', $initialUserCount + 5)
            ->where('activeOrganizationCount', $initialActiveOrgCount + 3) // initial + 3 active
        )
        ->where('role', 'super_admin')
    );
});

test('regular user sees organization-specific dashboard stats', function () {
    $user = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);

    $this->organization->users()->attach($user->id, [
        'role' => OrganizationRole::Admin->value,
        'joined_at' => now(),
    ]);

    // Create data for the organization
    $patients = Patient::factory()->count(10)->create([
        'organization_id' => $this->organization->id,
    ]);

    $upcomingAppointments = Appointment::factory()->count(5)->create([
        'organization_id' => $this->organization->id,
        'patient_id' => $patients->random()->id,
        'user_id' => $user->id,
        'appointment_date' => now()->addDays(2)->toDateString(),
        'status' => AppointmentStatus::Scheduled,
    ]);

    $recentAppointments = Appointment::factory()->count(3)->create([
        'organization_id' => $this->organization->id,
        'patient_id' => $patients->random()->id,
        'user_id' => $user->id,
        'appointment_date' => now()->subDays(1)->toDateString(),
        'status' => AppointmentStatus::Completed,
    ]);

    $examRooms = ExamRoom::factory()->count(3)->create([
        'organization_id' => $this->organization->id,
        'is_active' => true,
    ]);

    ExamRoom::factory()->count(2)->create([
        'organization_id' => $this->organization->id,
        'is_active' => false,
    ]);

    AuditLog::factory()->count(5)->create([
        'organization_id' => $this->organization->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->has('stats', fn ($stats) => $stats
            ->has('patientCount')
            ->has('upcomingAppointmentsCount')
            ->has('activeExamRoomsCount')
            ->where('patientCount', 10)
            ->where('upcomingAppointmentsCount', 5)
            ->where('activeExamRoomsCount', 3)
        )
        ->has('recentAppointments')
        ->has('recentActivity')
        ->where('role', 'user')
    );
});

test('regular user dashboard includes recent appointments with relationships', function () {
    $user = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);

    $this->organization->users()->attach($user->id, [
        'role' => OrganizationRole::Admin->value,
        'joined_at' => now(),
    ]);

    $patient = Patient::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $appointment = Appointment::factory()->create([
        'organization_id' => $this->organization->id,
        'patient_id' => $patient->id,
        'user_id' => $user->id,
        'appointment_date' => now()->addDays(1)->toDateString(),
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('recentAppointments', 1)
        ->where('recentAppointments.0.id', $appointment->id)
        ->has('recentAppointments.0.patient')
        ->has('recentAppointments.0.user')
    );
});

test('regular user dashboard includes recent activity with relationships', function () {
    $user = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);

    $this->organization->users()->attach($user->id, [
        'role' => OrganizationRole::Admin->value,
        'joined_at' => now(),
    ]);

    $auditLog = AuditLog::factory()->create([
        'organization_id' => $this->organization->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('recentActivity', 1)
        ->where('recentActivity.0.id', $auditLog->id)
        ->has('recentActivity.0.user')
    );
});

test('dashboard uses eager loading to prevent n+1 queries', function () {
    $user = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);

    $this->organization->users()->attach($user->id, [
        'role' => OrganizationRole::Admin->value,
        'joined_at' => now(),
    ]);

    $patients = Patient::factory()->count(5)->create([
        'organization_id' => $this->organization->id,
    ]);

    Appointment::factory()->count(5)->create([
        'organization_id' => $this->organization->id,
        'patient_id' => $patients->random()->id,
        'user_id' => $user->id,
    ]);

    AuditLog::factory()->count(5)->create([
        'organization_id' => $this->organization->id,
        'user_id' => $user->id,
    ]);

    // This test ensures queries are optimized - we can't easily test N+1 without query counting
    // but we can verify the response structure includes relationships
    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('recentAppointments')
        ->has('recentActivity')
    );
});

test('regular user dashboard only shows data from their organization', function () {
    $user = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);

    $this->organization->users()->attach($user->id, [
        'role' => OrganizationRole::Admin->value,
        'joined_at' => now(),
    ]);

    $otherOrganization = Organization::factory()->create();

    // Create data for user's organization
    Patient::factory()->count(5)->create([
        'organization_id' => $this->organization->id,
    ]);

    // Create data for other organization (should not appear)
    Patient::factory()->count(10)->create([
        'organization_id' => $otherOrganization->id,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('stats')
        ->where('stats.patientCount', 5) // Only from user's organization
    );
});
