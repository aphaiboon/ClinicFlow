<?php

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('completes full appointment scheduling flow', function () {
    $organization = \App\Models\Organization::factory()->create();
    $receptionist = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $organization->id]);
    $clinician = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $organization->id]);
    $organization->users()->attach($receptionist->id, ['role' => \App\Enums\OrganizationRole::Receptionist->value, 'joined_at' => now()]);
    $organization->users()->attach($clinician->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $patient = Patient::factory()->for($organization)->create();
    $room = ExamRoom::factory()->for($organization)->create(['is_active' => true]);

    $appointmentDate = Carbon::tomorrow();
    $appointmentTime = '10:00';

    $response = $this->actingAs($receptionist)
        ->get('/appointments/create');

    $response->assertSuccessful();

    $appointmentData = [
        'patient_id' => $patient->id,
        'user_id' => $clinician->id,
        'exam_room_id' => $room->id,
        'appointment_date' => $appointmentDate->toDateString(),
        'appointment_time' => $appointmentTime,
        'duration_minutes' => 30,
        'appointment_type' => AppointmentType::Routine->value,
        'notes' => 'Regular checkup',
    ];

    $response = $this->actingAs($receptionist)
        ->post('/appointments', $appointmentData);

    $response->assertRedirect();

    $appointment = Appointment::where('patient_id', $patient->id)
        ->whereDate('appointment_date', $appointmentDate->toDateString())
        ->first();

    expect($appointment)->not->toBeNull()
        ->and($appointment->status)->toBe(AppointmentStatus::Scheduled)
        ->and($appointment->user_id)->toBe($clinician->id)
        ->and($appointment->exam_room_id)->toBe($room->id)
        ->and($appointment->notes)->toBe('Regular checkup');

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'create',
        'resource_type' => 'Appointment',
        'resource_id' => $appointment->id,
    ]);
});

it('prevents scheduling conflicting appointments for same clinician', function () {
    $organization = \App\Models\Organization::factory()->create();
    $receptionist = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $organization->id]);
    $clinician = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $organization->id]);
    $organization->users()->attach($receptionist->id, ['role' => \App\Enums\OrganizationRole::Receptionist->value, 'joined_at' => now()]);
    $organization->users()->attach($clinician->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $patient1 = Patient::factory()->for($organization)->create();
    $patient2 = Patient::factory()->for($organization)->create();

    $appointmentDate = Carbon::tomorrow();
    $appointmentTime = '10:00';

    Appointment::factory()->for($organization)->create([
        'user_id' => $clinician->id,
        'appointment_date' => $appointmentDate->toDateString(),
        'appointment_time' => $appointmentTime,
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $conflictingData = [
        'patient_id' => $patient2->id,
        'user_id' => $clinician->id,
        'appointment_date' => $appointmentDate->toDateString(),
        'appointment_time' => '10:15',
        'duration_minutes' => 30,
        'appointment_type' => AppointmentType::Routine->value,
    ];

    $response = $this->actingAs($receptionist)
        ->post('/appointments', $conflictingData);

    $response->assertSessionHasErrors(['error']);

    expect(Appointment::where('patient_id', $patient2->id)->count())->toBe(0);
});

it('prevents scheduling conflicting appointments for same room', function () {
    $organization = \App\Models\Organization::factory()->create();
    $receptionist = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $organization->id]);
    $clinician1 = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $organization->id]);
    $clinician2 = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $organization->id]);
    $organization->users()->attach($receptionist->id, ['role' => \App\Enums\OrganizationRole::Receptionist->value, 'joined_at' => now()]);
    $organization->users()->attach($clinician1->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $organization->users()->attach($clinician2->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $patient1 = Patient::factory()->for($organization)->create();
    $patient2 = Patient::factory()->for($organization)->create();
    $room = ExamRoom::factory()->for($organization)->create(['is_active' => true]);

    $appointmentDate = Carbon::tomorrow();
    $appointmentTime = '10:00';

    Appointment::factory()->for($organization)->create([
        'exam_room_id' => $room->id,
        'appointment_date' => $appointmentDate->toDateString(),
        'appointment_time' => $appointmentTime,
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $conflictingData = [
        'patient_id' => $patient2->id,
        'user_id' => $clinician2->id,
        'exam_room_id' => $room->id,
        'appointment_date' => $appointmentDate->toDateString(),
        'appointment_time' => '10:15',
        'duration_minutes' => 30,
        'appointment_type' => AppointmentType::Routine->value,
    ];

    $response = $this->actingAs($receptionist)
        ->post('/appointments', $conflictingData);

    $response->assertSessionHasErrors(['error']);

    expect(Appointment::where('patient_id', $patient2->id)->count())->toBe(0);
});
