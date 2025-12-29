<?php

use App\Enums\AppointmentStatus;
use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->otherOrganization = Organization::factory()->create();

    $this->admin = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->organization->users()->attach($this->admin->id, [
        'role' => OrganizationRole::Admin->value,
        'joined_at' => now(),
    ]);

    $this->clinician = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->organization->users()->attach($this->clinician->id, [
        'role' => OrganizationRole::Clinician->value,
        'joined_at' => now(),
    ]);

    $this->otherClinician = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->organization->users()->attach($this->otherClinician->id, [
        'role' => OrganizationRole::Clinician->value,
        'joined_at' => now(),
    ]);

    $this->patient = Patient::factory()->for($this->organization)->create();
    $this->otherPatient = Patient::factory()->for($this->organization)->create();

    $this->examRoom = ExamRoom::factory()->for($this->organization)->create(['is_active' => true]);
    $this->otherExamRoom = ExamRoom::factory()->for($this->organization)->create(['is_active' => true]);
});

test('successfully reschedules appointment with no conflicts', function () {
    $tomorrow = Carbon::tomorrow();
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $newDate = $tomorrow->copy()->addDay();
    $newTime = '14:00';

    $response = actingAs($this->admin)->postJson("/appointments/{$appointment->id}/reschedule", [
        'appointment_date' => $newDate->toDateString(),
        'appointment_time' => $newTime,
        'duration_minutes' => 45,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Appointment rescheduled successfully.',
        ]);

    $appointment->refresh();
    expect($appointment->appointment_date->toDateString())->toBe($newDate->toDateString())
        ->and($appointment->appointment_time)->toBe($newTime.':00')
        ->and($appointment->duration_minutes)->toBe(45);
});

test('detects clinician conflict when rescheduling', function () {
    $tomorrow = Carbon::tomorrow();
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    // Create conflicting appointment for same clinician
    $conflictingAppointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->otherPatient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '14:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = actingAs($this->admin)->postJson("/appointments/{$appointment->id}/reschedule", [
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '14:15', // Overlaps with conflicting appointment
        'duration_minutes' => 30,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonStructure([
            'conflicts' => [
                '*' => [
                    'type',
                    'message',
                    'conflictingAppointments',
                ],
            ],
        ]);

    $conflicts = $response->json('conflicts');
    expect($conflicts)->toHaveCount(1)
        ->and($conflicts[0]['type'])->toBe('clinician')
        ->and($conflicts[0]['conflictingAppointments'])->toHaveCount(1)
        ->and($conflicts[0]['conflictingAppointments'][0]['id'])->toBe($conflictingAppointment->id);
});

test('detects exam room conflict when rescheduling', function () {
    $tomorrow = Carbon::tomorrow();
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    // Create conflicting appointment in same room
    $conflictingAppointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->otherPatient->id,
        'user_id' => $this->otherClinician->id,
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '14:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = actingAs($this->admin)->postJson("/appointments/{$appointment->id}/reschedule", [
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '14:15', // Overlaps with conflicting appointment
        'duration_minutes' => 30,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);

    $conflicts = $response->json('conflicts');
    expect($conflicts)->toHaveCount(1)
        ->and($conflicts[0]['type'])->toBe('room')
        ->and($conflicts[0]['conflictingAppointments'])->toHaveCount(1)
        ->and($conflicts[0]['conflictingAppointments'][0]['id'])->toBe($conflictingAppointment->id);
});

test('detects both clinician and room conflicts when rescheduling', function () {
    $tomorrow = Carbon::tomorrow();
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    // Create conflicting appointment with same clinician AND same room
    $conflictingAppointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->otherPatient->id,
        'user_id' => $this->clinician->id,
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '14:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = actingAs($this->admin)->postJson("/appointments/{$appointment->id}/reschedule", [
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '14:15',
        'duration_minutes' => 30,
    ]);

    $response->assertStatus(422);

    $conflicts = $response->json('conflicts');
    expect($conflicts)->toHaveCount(2)
        ->and(collect($conflicts)->pluck('type'))->toContain('clinician', 'room');
});

test('unauthorized user cannot reschedule appointment', function () {
    $unauthorizedUser = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->otherOrganization->id,
    ]);
    $this->otherOrganization->users()->attach($unauthorizedUser->id, [
        'role' => OrganizationRole::Admin->value,
        'joined_at' => now(),
    ]);

    $tomorrow = Carbon::tomorrow();
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = actingAs($unauthorizedUser)->postJson("/appointments/{$appointment->id}/reschedule", [
        'appointment_date' => $tomorrow->addDay()->toDateString(),
        'appointment_time' => '14:00',
    ]);

    $response->assertForbidden();
});

test('validates appointment date is not in the past', function () {
    $yesterday = Carbon::yesterday();
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => Carbon::tomorrow()->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = actingAs($this->admin)->postJson("/appointments/{$appointment->id}/reschedule", [
        'appointment_date' => $yesterday->toDateString(),
        'appointment_time' => '14:00',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['appointment_date']);
});

test('validates appointment time format', function () {
    $tomorrow = Carbon::tomorrow();
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = actingAs($this->admin)->postJson("/appointments/{$appointment->id}/reschedule", [
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => 'invalid-time',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['appointment_time']);
});

test('validates duration minutes is positive', function () {
    $tomorrow = Carbon::tomorrow();
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = actingAs($this->admin)->postJson("/appointments/{$appointment->id}/reschedule", [
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '14:00',
        'duration_minutes' => 5, // Less than minimum 15
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['duration_minutes']);
});

test('ignores cancelled appointments when checking conflicts', function () {
    $tomorrow = Carbon::tomorrow();
    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    // Create cancelled appointment that would conflict
    Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->otherPatient->id,
        'user_id' => $this->clinician->id,
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '14:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Cancelled,
    ]);

    $response = actingAs($this->admin)->postJson("/appointments/{$appointment->id}/reschedule", [
        'appointment_date' => $tomorrow->toDateString(),
        'appointment_time' => '14:15',
        'duration_minutes' => 30,
    ]);

    $response->assertSuccessful();
});
