<?php

use App\Enums\AppointmentStatus;
use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

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

    $this->receptionist = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->organization->users()->attach($this->receptionist->id, [
        'role' => OrganizationRole::Receptionist->value,
        'joined_at' => now(),
    ]);

    $this->patient = Patient::factory()->for($this->organization)->create();
    $this->otherPatient = Patient::factory()->for($this->organization)->create();
    $this->otherOrgPatient = Patient::factory()->for($this->otherOrganization)->create();

    $this->examRoom = ExamRoom::factory()->for($this->organization)->create();
    $this->otherExamRoom = ExamRoom::factory()->for($this->organization)->create();
});

test('unauthenticated users cannot access calendar endpoint', function () {
    $response = get('/appointments/calendar');

    $response->assertRedirect(route('login'));
});

test('admin sees all organization appointments in calendar', function () {
    $today = now()->toDateString();
    $tomorrow = now()->addDay()->toDateString();

    $appointment1 = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $today,
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    $appointment2 = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->otherPatient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $tomorrow,
        'appointment_time' => '14:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    // Appointment from other organization should not appear
    Appointment::factory()->for($this->otherOrganization)->create([
        'appointment_date' => $today,
        'appointment_time' => '11:00',
    ]);

    $response = actingAs($this->admin)->get('/appointments/calendar?start_date='.$today.'&end_date='.$tomorrow);

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'events' => [
            '*' => [
                'id',
                'title',
                'start',
                'end',
                'extendedProps' => [
                    'appointmentId',
                    'patientId',
                    'patientName',
                    'clinicianId',
                    'clinicianName',
                    'status',
                ],
            ],
        ],
    ]);

    $events = $response->json('events');
    expect($events)->toHaveCount(2)
        ->and(collect($events)->pluck('extendedProps.appointmentId'))
        ->toContain($appointment1->id, $appointment2->id);
});

test('clinician sees only their scheduled appointments in calendar', function () {
    $today = now()->toDateString();

    $myAppointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $today,
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    $otherClinician = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->organization->users()->attach($otherClinician->id, [
        'role' => OrganizationRole::Clinician->value,
        'joined_at' => now(),
    ]);

    $otherAppointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->otherPatient->id,
        'user_id' => $otherClinician->id,
        'appointment_date' => $today,
        'appointment_time' => '14:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    $response = actingAs($this->clinician)->get('/appointments/calendar?start_date='.$today.'&end_date='.$today);

    $response->assertSuccessful();
    $events = $response->json('events');
    expect($events)->toHaveCount(1)
        ->and($events[0]['extendedProps']['appointmentId'])->toBe($myAppointment->id);
});

test('receptionist sees all organization appointments in calendar', function () {
    $today = now()->toDateString();

    $appointment1 = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $today,
        'appointment_time' => '10:00',
    ]);

    $appointment2 = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->otherPatient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $today,
        'appointment_time' => '14:00',
    ]);

    $response = actingAs($this->receptionist)->get('/appointments/calendar?start_date='.$today.'&end_date='.$today);

    $response->assertSuccessful();
    $events = $response->json('events');
    expect($events)->toHaveCount(2);
});

test('calendar endpoint filters by date range', function () {
    $today = now()->toDateString();
    $tomorrow = now()->addDay()->toDateString();
    $nextWeek = now()->addWeek()->toDateString();

    Appointment::factory()->for($this->organization)->create([
        'appointment_date' => $today,
        'appointment_time' => '10:00',
    ]);

    Appointment::factory()->for($this->organization)->create([
        'appointment_date' => $tomorrow,
        'appointment_time' => '14:00',
    ]);

    Appointment::factory()->for($this->organization)->create([
        'appointment_date' => $nextWeek,
        'appointment_time' => '16:00',
    ]);

    $response = actingAs($this->admin)->get('/appointments/calendar?start_date='.$today.'&end_date='.$tomorrow);

    $response->assertSuccessful();
    $events = $response->json('events');
    expect($events)->toHaveCount(2);
});

test('calendar endpoint filters by exam room', function () {
    $today = now()->toDateString();

    $appointment1 = Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => $today,
        'appointment_time' => '10:00',
    ]);

    $appointment2 = Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->otherExamRoom->id,
        'appointment_date' => $today,
        'appointment_time' => '14:00',
    ]);

    Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => null,
        'appointment_date' => $today,
        'appointment_time' => '16:00',
    ]);

    $response = actingAs($this->admin)->get('/appointments/calendar?start_date='.$today.'&end_date='.$today.'&exam_room_id='.$this->examRoom->id);

    $response->assertSuccessful();
    $events = $response->json('events');
    expect($events)->toHaveCount(1)
        ->and($events[0]['extendedProps']['appointmentId'])->toBe($appointment1->id);
});

test('calendar endpoint includes all required relationships', function () {
    $today = now()->toDateString();

    $appointment = Appointment::factory()->for($this->organization)->create([
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'exam_room_id' => $this->examRoom->id,
        'appointment_date' => $today,
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
    ]);

    $response = actingAs($this->admin)->get('/appointments/calendar?start_date='.$today.'&end_date='.$today);

    $response->assertSuccessful();
    $events = $response->json('events');
    $event = $events[0];

    expect($event['extendedProps'])
        ->toHaveKey('appointmentId')
        ->toHaveKey('patientId')
        ->toHaveKey('patientName')
        ->toHaveKey('clinicianId')
        ->toHaveKey('clinicianName')
        ->toHaveKey('examRoomId')
        ->toHaveKey('examRoomName')
        ->toHaveKey('status')
        ->toHaveKey('appointmentType')
        ->toHaveKey('durationMinutes')
        ->and($event['extendedProps']['appointmentId'])->toBe($appointment->id)
        ->and($event['extendedProps']['patientId'])->toBe($this->patient->id)
        ->and($event['extendedProps']['clinicianId'])->toBe($this->clinician->id)
        ->and($event['extendedProps']['examRoomId'])->toBe($this->examRoom->id);
});

test('calendar endpoint formats events correctly for FullCalendar', function () {
    $today = now()->toDateString();
    $appointment = Appointment::factory()->for($this->organization)->create([
        'appointment_date' => $today,
        'appointment_time' => '10:30',
        'duration_minutes' => 45,
    ]);

    $response = actingAs($this->admin)->get('/appointments/calendar?start_date='.$today.'&end_date='.$today);

    $response->assertSuccessful();
    $events = $response->json('events');
    $event = $events[0];

    expect($event)
        ->toHaveKey('id')
        ->toHaveKey('title')
        ->toHaveKey('start')
        ->toHaveKey('end')
        ->toHaveKey('extendedProps')
        ->and($event['start'])->toContain($today)
        ->and($event['start'])->toContain('10:30')
        ->and($event['end'])->toContain($today)
        ->and($event['end'])->toContain('11:15'); // 10:30 + 45 minutes
});

test('calendar endpoint uses eager loading to prevent n+1 queries', function () {
    $today = now()->toDateString();

    Appointment::factory()->for($this->organization)->count(10)->create([
        'appointment_date' => $today,
    ]);

    $queryCount = 0;
    \DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    actingAs($this->admin)->get('/appointments/calendar?start_date='.$today.'&end_date='.$today);

    // Should be minimal queries: 1 for appointments with eager loading, maybe 1-2 more for organization/user checks
    // Without eager loading, this would be 1 + 10*3 = 31 queries (appointments + patients + users + examRooms)
    expect($queryCount)->toBeLessThan(10);
});

test('calendar endpoint handles missing start_date and end_date', function () {
    $response = actingAs($this->admin)->get('/appointments/calendar');

    $response->assertSuccessful();
    // Should default to current week or month
    $response->assertJsonStructure(['events']);
});

test('calendar endpoint excludes cancelled appointments by default', function () {
    $today = now()->toDateString();

    $scheduledAppointment = Appointment::factory()->for($this->organization)->create([
        'appointment_date' => $today,
        'appointment_time' => '10:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    $cancelledAppointment = Appointment::factory()->for($this->organization)->create([
        'appointment_date' => $today,
        'appointment_time' => '14:00',
        'status' => AppointmentStatus::Cancelled,
    ]);

    $response = actingAs($this->admin)->get('/appointments/calendar?start_date='.$today.'&end_date='.$today);

    $response->assertSuccessful();
    $events = $response->json('events');
    $appointmentIds = collect($events)->pluck('extendedProps.appointmentId')->toArray();

    expect($appointmentIds)->toContain($scheduledAppointment->id)
        ->and($appointmentIds)->not->toContain($cancelledAppointment->id);
});
