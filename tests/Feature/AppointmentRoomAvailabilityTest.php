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

    $this->examRoom1 = ExamRoom::factory()->for($this->organization)->create([
        'name' => 'Room 1',
        'room_number' => '101',
        'is_active' => true,
    ]);
    $this->examRoom2 = ExamRoom::factory()->for($this->organization)->create([
        'name' => 'Room 2',
        'room_number' => '102',
        'is_active' => true,
    ]);
    $this->inactiveRoom = ExamRoom::factory()->for($this->organization)->create([
        'name' => 'Inactive Room',
        'room_number' => '103',
        'is_active' => false,
    ]);
    $this->otherOrgRoom = ExamRoom::factory()->for($this->otherOrganization)->create([
        'is_active' => true,
    ]);
});

test('unauthenticated users cannot access room availability endpoint', function () {
    $response = get('/appointments/available-rooms');

    $response->assertRedirect(route('login'));
});

test('returns all active rooms as available when no appointments exist', function () {
    $today = now()->addDay();
    $startDate = $today->copy()->setTime(10, 0)->toIso8601String();
    $endDate = $today->copy()->setTime(18, 0)->toIso8601String();

    $response = actingAs($this->admin)->get('/appointments/available-rooms?start_date='.urlencode($startDate).'&end_date='.urlencode($endDate));

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'rooms' => [
            '*' => [
                'roomId',
                'roomName',
                'roomNumber',
                'isActive',
                'availability',
                'conflictingAppointments',
            ],
        ],
    ]);

    $rooms = $response->json('rooms');
    expect($rooms)->toHaveCount(2)
        ->and(collect($rooms)->pluck('roomId'))->toContain($this->examRoom1->id, $this->examRoom2->id)
        ->and(collect($rooms)->pluck('roomId'))->not->toContain($this->inactiveRoom->id)
        ->and(collect($rooms)->pluck('roomId'))->not->toContain($this->otherOrgRoom->id)
        ->and(collect($rooms)->where('roomId', $this->examRoom1->id)->first()['availability'])->toBe('available')
        ->and(collect($rooms)->where('roomId', $this->examRoom2->id)->first()['availability'])->toBe('available');
});

test('marks room as busy when appointment exists in time range', function () {
    $today = now()->addDay();
    $appointment = Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->examRoom1->id,
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $today->toDateString(),
        'appointment_time' => '14:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $startDate = $today->copy()->setTime(10, 0)->toIso8601String();
    $endDate = $today->copy()->setTime(18, 0)->toIso8601String();

    $response = actingAs($this->admin)->get('/appointments/available-rooms?start_date='.urlencode($startDate).'&end_date='.urlencode($endDate));

    $response->assertSuccessful();
    $rooms = $response->json('rooms');
    $room1Availability = collect($rooms)->where('roomId', $this->examRoom1->id)->first();

    expect($room1Availability['availability'])->toBe('busy')
        ->and($room1Availability['conflictingAppointments'])->toHaveCount(1)
        ->and($room1Availability['conflictingAppointments'][0]['id'])->toBe($appointment->id);
});

test('marks room as available when appointment is cancelled', function () {
    $today = now()->addDay();
    Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->examRoom1->id,
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $today->toDateString(),
        'appointment_time' => '14:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Cancelled,
    ]);

    $startDate = $today->copy()->setTime(10, 0)->toIso8601String();
    $endDate = $today->copy()->setTime(18, 0)->toIso8601String();

    $response = actingAs($this->admin)->get('/appointments/available-rooms?start_date='.urlencode($startDate).'&end_date='.urlencode($endDate));

    $response->assertSuccessful();
    $rooms = $response->json('rooms');
    $room1Availability = collect($rooms)->where('roomId', $this->examRoom1->id)->first();

    expect($room1Availability['availability'])->toBe('available')
        ->and($room1Availability['conflictingAppointments'])->toHaveCount(0);
});

test('filters by specific room when roomId provided', function () {
    $today = now()->addDay();
    $appointment1 = Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->examRoom1->id,
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $today->toDateString(),
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);
    Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->examRoom2->id,
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $today->toDateString(),
        'appointment_time' => '11:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $startDate = $today->copy()->setTime(9, 0)->toIso8601String();
    $endDate = $today->copy()->setTime(12, 0)->toIso8601String();

    $response = actingAs($this->admin)->get('/appointments/available-rooms?start_date='.urlencode($startDate).'&end_date='.urlencode($endDate).'&room_id='.$this->examRoom1->id);

    $response->assertSuccessful();
    $rooms = $response->json('rooms');
    expect($rooms)->toHaveCount(1)
        ->and($rooms[0]['roomId'])->toBe($this->examRoom1->id)
        ->and($rooms[0]['availability'])->toBe('busy')
        ->and($rooms[0]['conflictingAppointments'])->toHaveCount(1)
        ->and($rooms[0]['conflictingAppointments'][0]['id'])->toBe($appointment1->id);
});

test('handles missing start_date and end_date parameters', function () {
    $response = actingAs($this->admin)->get('/appointments/available-rooms');

    $response->assertSuccessful();
    // Should default to current day or return empty/error
    $response->assertJsonStructure(['rooms']);
});

test('returns rooms from user organization only', function () {
    $today = now()->addDay();
    Appointment::factory()->for($this->otherOrganization)->create([
        'exam_room_id' => $this->otherOrgRoom->id,
        'appointment_date' => $today->toDateString(),
        'appointment_time' => '14:00',
        'status' => AppointmentStatus::Scheduled,
    ]);

    $startDate = $today->copy()->setTime(10, 0)->toIso8601String();
    $endDate = $today->copy()->setTime(18, 0)->toIso8601String();

    $response = actingAs($this->admin)->get('/appointments/available-rooms?start_date='.urlencode($startDate).'&end_date='.urlencode($endDate));

    $response->assertSuccessful();
    $rooms = $response->json('rooms');
    $roomIds = collect($rooms)->pluck('roomId')->toArray();

    expect($roomIds)->not->toContain($this->otherOrgRoom->id);
});

test('handles multiple appointments in same room', function () {
    $today = now()->addDay();
    $appointment1 = Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->examRoom1->id,
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $today->toDateString(),
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);
    $appointment2 = Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->examRoom1->id,
        'patient_id' => $this->otherPatient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $today->toDateString(),
        'appointment_time' => '10:45',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $startDate = $today->copy()->setTime(9, 0)->toIso8601String();
    $endDate = $today->copy()->setTime(12, 0)->toIso8601String();

    $response = actingAs($this->admin)->get('/appointments/available-rooms?start_date='.urlencode($startDate).'&end_date='.urlencode($endDate));

    $response->assertSuccessful();
    $rooms = $response->json('rooms');
    $room1Availability = collect($rooms)->where('roomId', $this->examRoom1->id)->first();

    expect($room1Availability['availability'])->toBe('busy')
        ->and($room1Availability['conflictingAppointments'])->toHaveCount(2)
        ->and(collect($room1Availability['conflictingAppointments'])->pluck('id'))
        ->toContain($appointment1->id, $appointment2->id);
});

test('receptionist can access room availability', function () {
    $today = now()->addDay();
    $startDate = $today->copy()->setTime(10, 0)->toIso8601String();
    $endDate = $today->copy()->setTime(18, 0)->toIso8601String();

    $response = actingAs($this->receptionist)->get('/appointments/available-rooms?start_date='.urlencode($startDate).'&end_date='.urlencode($endDate));

    $response->assertSuccessful();
    $response->assertJsonStructure(['rooms']);
});

test('clinician can access room availability', function () {
    $today = now()->addDay();
    $startDate = $today->copy()->setTime(10, 0)->toIso8601String();
    $endDate = $today->copy()->setTime(18, 0)->toIso8601String();

    $response = actingAs($this->clinician)->get('/appointments/available-rooms?start_date='.urlencode($startDate).'&end_date='.urlencode($endDate));

    $response->assertSuccessful();
    $response->assertJsonStructure(['rooms']);
});
