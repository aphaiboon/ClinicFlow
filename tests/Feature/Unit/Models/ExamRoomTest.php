<?php

use App\Models\Appointment;
use App\Models\ExamRoom;

beforeEach(function () {
    $this->organization = \App\Models\Organization::factory()->create();
});

it('has fillable attributes', function () {
    $room = new ExamRoom;
    $fillable = [
        'organization_id',
        'room_number',
        'name',
        'floor',
        'equipment',
        'capacity',
        'is_active',
        'notes',
    ];

    expect($room->getFillable())->toBe($fillable);
});

it('casts equipment to array', function () {
    $equipment = ['stethoscope', 'blood_pressure_monitor', 'thermometer'];
    $room = ExamRoom::factory()->for($this->organization)->create(['equipment' => $equipment]);

    expect($room->equipment)->toBe($equipment)
        ->and($room->equipment)->toBeArray();
});

it('casts is_active to boolean', function () {
    $room = ExamRoom::factory()->for($this->organization)->create(['is_active' => true]);

    expect($room->is_active)->toBeTrue();

    $room->is_active = false;
    expect($room->is_active)->toBeFalse();
});

it('casts floor and capacity to integer', function () {
    $room = ExamRoom::factory()->for($this->organization)->create(['floor' => '2', 'capacity' => '3']);

    expect($room->floor)->toBe(2)
        ->and($room->capacity)->toBe(3);
});

it('has appointments relationship', function () {
    $room = ExamRoom::factory()->for($this->organization)->create();
    Appointment::factory()->for($this->organization)->count(2)->create(['exam_room_id' => $room->id]);

    expect($room->appointments)->toHaveCount(2)
        ->and($room->appointments->first())->toBeInstanceOf(Appointment::class);
});

it('can scope active rooms', function () {
    ExamRoom::factory()->for($this->organization)->create(['is_active' => true]);
    ExamRoom::factory()->for($this->organization)->create(['is_active' => true]);
    ExamRoom::factory()->for($this->organization)->create(['is_active' => false]);

    $activeRooms = ExamRoom::active()->get();

    expect($activeRooms)->toHaveCount(2);
});
