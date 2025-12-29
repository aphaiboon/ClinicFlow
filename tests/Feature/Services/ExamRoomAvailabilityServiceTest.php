<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\ExamRoomAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['current_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->user->id, ['role' => \App\Enums\OrganizationRole::Admin->value, 'joined_at' => now()]);
    $this->actingAs($this->user);

    $this->service = app(ExamRoomAvailabilityService::class);
    $this->patient = Patient::factory()->for($this->organization)->create();
    $this->otherPatient = Patient::factory()->for($this->organization)->create();
    $this->clinician = User::factory()->create(['current_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->clinician->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);

    $this->room1 = ExamRoom::factory()->for($this->organization)->create(['name' => 'Room 1', 'is_active' => true]);
    $this->room2 = ExamRoom::factory()->for($this->organization)->create(['name' => 'Room 2', 'is_active' => true]);
    $this->inactiveRoom = ExamRoom::factory()->for($this->organization)->create(['name' => 'Inactive Room', 'is_active' => false]);
});

test('returns all active rooms as available when no appointments exist', function () {
    $date = Carbon::now()->addDay();
    $start = $date->copy()->setTime(10, 0);
    $end = $date->copy()->setTime(18, 0);

    $availability = $this->service->getAvailabilityForDateRange($start, $end, null, $this->organization->id);

    expect($availability)->toHaveCount(2)
        ->and($availability->pluck('roomId'))->toContain($this->room1->id, $this->room2->id)
        ->and($availability->where('roomId', $this->room1->id)->first()['availability'])->toBe('available')
        ->and($availability->where('roomId', $this->room2->id)->first()['availability'])->toBe('available');
});

test('marks room as busy when appointment exists in time range', function () {
    $date = Carbon::now()->addDay();
    $appointment = Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->room1->id,
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => '14:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $start = $date->copy()->setTime(10, 0);
    $end = $date->copy()->setTime(18, 0);

    $availability = $this->service->getAvailabilityForDateRange($start, $end, null, $this->organization->id);

    $room1Availability = $availability->where('roomId', $this->room1->id)->first();
    expect($room1Availability['availability'])->toBe('busy')
        ->and($room1Availability['conflictingAppointments'])->toHaveCount(1)
        ->and($room1Availability['conflictingAppointments'][0]['id'])->toBe($appointment->id);
});

test('marks room as available when appointment is outside time range', function () {
    $date = Carbon::now()->addDay();
    Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->room1->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => '09:00', // Before range
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $start = $date->copy()->setTime(10, 0);
    $end = $date->copy()->setTime(18, 0);

    $availability = $this->service->getAvailabilityForDateRange($start, $end, null, $this->organization->id);

    $room1Availability = $availability->where('roomId', $this->room1->id)->first();
    expect($room1Availability['availability'])->toBe('available');
});

test('marks room as available when appointment is cancelled', function () {
    $date = Carbon::now()->addDay();
    Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->room1->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => '14:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Cancelled,
    ]);

    $start = $date->copy()->setTime(10, 0);
    $end = $date->copy()->setTime(18, 0);

    $availability = $this->service->getAvailabilityForDateRange($start, $end, null, $this->organization->id);

    $room1Availability = $availability->where('roomId', $this->room1->id)->first();
    expect($room1Availability['availability'])->toBe('available');
});

test('marks room as busy when appointment overlaps time range', function () {
    $date = Carbon::now()->addDay();
    Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->room1->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => '09:30', // Starts before range, ends during range
        'duration_minutes' => 60, // Ends at 10:30
        'status' => AppointmentStatus::Scheduled,
    ]);

    $start = $date->copy()->setTime(10, 0);
    $end = $date->copy()->setTime(18, 0);

    $availability = $this->service->getAvailabilityForDateRange($start, $end, null, $this->organization->id);

    $room1Availability = $availability->where('roomId', $this->room1->id)->first();
    expect($room1Availability['availability'])->toBe('busy');
});

test('filters by specific room when roomId provided', function () {
    $date = Carbon::now()->addDay();
    $appointment1 = Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->room1->id,
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);
    Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->room2->id,
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => '11:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $start = $date->copy()->setTime(9, 0);
    $end = $date->copy()->setTime(12, 0);

    $availability = $this->service->getAvailabilityForDateRange($start, $end, $this->room1->id, $this->organization->id);

    expect($availability)->toHaveCount(1)
        ->and($availability->first()['roomId'])->toBe($this->room1->id)
        ->and($availability->first()['availability'])->toBe('busy')
        ->and($availability->first()['conflictingAppointments'])->toHaveCount(1)
        ->and($availability->first()['conflictingAppointments'][0]['id'])->toBe($appointment1->id);
});

test('excludes inactive rooms from availability', function () {
    $date = Carbon::now()->addDay();
    Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->inactiveRoom->id,
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $start = $date->copy()->setTime(9, 0);
    $end = $date->copy()->setTime(12, 0);

    $availability = $this->service->getAvailabilityForDateRange($start, $end, null, $this->organization->id);

    $roomIds = $availability->pluck('roomId')->toArray();
    expect($roomIds)->not->toContain($this->inactiveRoom->id);
});

test('handles multiple appointments in same room', function () {
    $date = Carbon::now()->addDay();
    $appointment1 = Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->room1->id,
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => '10:00',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);
    $appointment2 = Appointment::factory()->for($this->organization)->create([
        'exam_room_id' => $this->room1->id,
        'patient_id' => $this->patient->id,
        'user_id' => $this->clinician->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => '10:45',
        'duration_minutes' => 30,
        'status' => AppointmentStatus::Scheduled,
    ]);

    $start = $date->copy()->setTime(9, 0);
    $end = $date->copy()->setTime(12, 0);

    $availability = $this->service->getAvailabilityForDateRange($start, $end, null, $this->organization->id);

    $room1Availability = $availability->where('roomId', $this->room1->id)->first();
    expect($room1Availability['availability'])->toBe('busy')
        ->and($room1Availability['conflictingAppointments'])->toHaveCount(2)
        ->and(collect($room1Availability['conflictingAppointments'])->pluck('id'))
        ->toContain($appointment1->id, $appointment2->id);
});

test('returns room availability with correct structure', function () {
    $date = Carbon::now()->addDay();
    $start = $date->copy()->setTime(10, 0);
    $end = $date->copy()->setTime(18, 0);

    $availability = $this->service->getAvailabilityForDateRange($start, $end, null, $this->organization->id);

    $room = $availability->first();
    expect($room)
        ->toHaveKeys([
            'roomId',
            'roomName',
            'roomNumber',
            'isActive',
            'availability',
            'conflictingAppointments',
        ])
        ->and($room['isActive'])->toBeTrue()
        ->and($room['conflictingAppointments'])->toBeArray();
});
