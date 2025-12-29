<?php

use App\Enums\OrganizationRole;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ExamRoom;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;

it('has fillable attributes', function () {
    $organization = new Organization;
    $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'tax_id',
        'npi_number',
        'practice_type',
        'license_number',
        'is_active',
        'operating_hours_start',
        'operating_hours_end',
        'default_time_slot_interval',
    ];

    expect($organization->getFillable())->toBe($fillable);
});

it('casts is_active to boolean', function () {
    $organization = Organization::factory()->create(['is_active' => true]);

    expect($organization->is_active)->toBeTrue();

    $organization->is_active = false;
    expect($organization->is_active)->toBeFalse();
});

it('has users relationship via pivot', function () {
    $organization = Organization::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $organization->users()->attach($user1->id, ['role' => OrganizationRole::Owner->value, 'joined_at' => now()]);
    $organization->users()->attach($user2->id, ['role' => OrganizationRole::Admin->value, 'joined_at' => now()]);

    expect($organization->users)->toHaveCount(2)
        ->and($organization->users->first())->toBeInstanceOf(User::class);
});

it('can access pivot role on users relationship', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->users()->attach($user->id, ['role' => OrganizationRole::Clinician->value, 'joined_at' => now()]);

    $pivot = $organization->users()->where('user_id', $user->id)->first()->pivot;

    expect($pivot->role)->toBe(OrganizationRole::Clinician->value);
});

it('has patients relationship', function () {
    $organization = Organization::factory()->create();

    Patient::factory()->count(3)->create(['organization_id' => $organization->id]);

    $organization->refresh();
    expect($organization->patients)->toHaveCount(3)
        ->and($organization->patients->first())->toBeInstanceOf(Patient::class);
});

it('has appointments relationship', function () {
    $organization = Organization::factory()->create();
    $patient = Patient::factory()->create(['organization_id' => $organization->id]);
    $user = User::factory()->create();
    Appointment::factory()->count(2)->create([
        'organization_id' => $organization->id,
        'patient_id' => $patient->id,
        'user_id' => $user->id,
    ]);

    $organization->refresh();
    expect($organization->appointments)->toHaveCount(2)
        ->and($organization->appointments->first())->toBeInstanceOf(Appointment::class);
});

it('has exam_rooms relationship', function () {
    $organization = Organization::factory()->create();
    ExamRoom::factory()->count(2)->create(['organization_id' => $organization->id]);

    expect($organization->examRooms)->toHaveCount(2)
        ->and($organization->examRooms->first())->toBeInstanceOf(ExamRoom::class);
});

it('has audit_logs relationship', function () {
    $organization = Organization::factory()->create();
    AuditLog::factory()->count(2)->create(['organization_id' => $organization->id]);

    expect($organization->auditLogs)->toHaveCount(2)
        ->and($organization->auditLogs->first())->toBeInstanceOf(AuditLog::class);
});

it('can scope active organizations', function () {
    Organization::factory()->create(['is_active' => true]);
    Organization::factory()->create(['is_active' => true]);
    Organization::factory()->create(['is_active' => false]);

    $activeOrganizations = Organization::active()->get();

    expect($activeOrganizations)->toHaveCount(2);
});

it('can get owners via owners method', function () {
    $organization = Organization::factory()->create();
    $owner1 = User::factory()->create();
    $owner2 = User::factory()->create();
    $admin = User::factory()->create();

    $organization->users()->attach($owner1->id, ['role' => OrganizationRole::Owner->value, 'joined_at' => now()]);
    $organization->users()->attach($owner2->id, ['role' => OrganizationRole::Owner->value, 'joined_at' => now()]);
    $organization->users()->attach($admin->id, ['role' => OrganizationRole::Admin->value, 'joined_at' => now()]);

    $owners = $organization->owners()->get();

    expect($owners)->toHaveCount(2)
        ->and($owners->pluck('id')->toArray())->toContain($owner1->id, $owner2->id)
        ->and($owners->pluck('id')->toArray())->not->toContain($admin->id);
});

it('can get admins via admins method', function () {
    $organization = Organization::factory()->create();
    $admin1 = User::factory()->create();
    $admin2 = User::factory()->create();
    $owner = User::factory()->create();

    $organization->users()->attach($admin1->id, ['role' => OrganizationRole::Admin->value, 'joined_at' => now()]);
    $organization->users()->attach($admin2->id, ['role' => OrganizationRole::Admin->value, 'joined_at' => now()]);
    $organization->users()->attach($owner->id, ['role' => OrganizationRole::Owner->value, 'joined_at' => now()]);

    $admins = $organization->admins()->get();

    expect($admins)->toHaveCount(2)
        ->and($admins->pluck('id')->toArray())->toContain($admin1->id, $admin2->id)
        ->and($admins->pluck('id')->toArray())->not->toContain($owner->id);
});

it('can get all members via members method', function () {
    $organization = Organization::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $organization->users()->attach($user1->id, ['role' => OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $organization->users()->attach($user2->id, ['role' => OrganizationRole::Receptionist->value, 'joined_at' => now()]);

    $members = $organization->members()->get();

    expect($members)->toHaveCount(2);
});
