<?php

use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\User;
use App\Services\ExamRoomService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->service = app(ExamRoomService::class);
});

it('can create a room', function () {
    $data = [
        'room_number' => 'R101',
        'name' => 'Exam Room 101',
        'floor' => 1,
        'equipment' => ['stethoscope', 'thermometer'],
        'capacity' => 2,
        'is_active' => true,
    ];

    $room = $this->service->createRoom($data);

    expect($room)->toBeInstanceOf(ExamRoom::class)
        ->and($room->room_number)->toBe('R101')
        ->and($room->name)->toBe('Exam Room 101');

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'create',
        'resource_type' => 'ExamRoom',
        'resource_id' => $room->id,
    ]);
});

it('can update a room', function () {
    $room = ExamRoom::factory()->create();

    $updateData = ['name' => 'Updated Room Name'];
    $updated = $this->service->updateRoom($room, $updateData);

    expect($updated->name)->toBe('Updated Room Name');

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'update',
        'resource_type' => 'ExamRoom',
        'resource_id' => $room->id,
    ]);
});

it('can activate a room', function () {
    $room = ExamRoom::factory()->inactive()->create();

    $activated = $this->service->activateRoom($room);

    expect($activated->is_active)->toBeTrue();
});

it('can deactivate a room', function () {
    $room = ExamRoom::factory()->create(['is_active' => true]);

    $deactivated = $this->service->deactivateRoom($room);

    expect($deactivated->is_active)->toBeFalse();
});

it('excludes inactive rooms from available rooms', function () {
    $activeRoom = ExamRoom::factory()->create(['is_active' => true]);
    $inactiveRoom = ExamRoom::factory()->create(['is_active' => false]);

    $date = Carbon::now()->addDays(5);
    $time = Carbon::createFromTime(10, 0);

    $availableRooms = $this->service->getAvailableRooms($date, $time, 30);

    $roomIds = $availableRooms->pluck('id')->toArray();
    expect($roomIds)->toContain($activeRoom->id)
        ->and($roomIds)->not->toContain($inactiveRoom->id);
});

it('excludes rooms with overlapping appointments', function () {
    $room = ExamRoom::factory()->create(['is_active' => true]);
    $date = Carbon::now()->addDays(5);
    $startTime = Carbon::createFromTime(10, 0);

    Appointment::factory()->create([
        'exam_room_id' => $room->id,
        'appointment_date' => $date->toDateString(),
        'appointment_time' => $startTime->toTimeString(),
        'duration_minutes' => 30,
        'status' => \App\Enums\AppointmentStatus::Scheduled,
    ]);

    $overlappingTime = $startTime->copy()->addMinutes(15);
    $availableRooms = $this->service->getAvailableRooms($date, $overlappingTime, 30);

    expect($availableRooms)->not->toContain($room);
});
