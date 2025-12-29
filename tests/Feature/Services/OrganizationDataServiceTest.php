<?php

use App\Enums\OrganizationRole;
use App\Models\ExamRoom;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Services\OrganizationDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->service = new OrganizationDataService;
});

test('returns empty array when organization is null', function () {
    expect($this->service->getClinicians(null))->toBe([])
        ->and($this->service->getPatients(null))->toBe([])
        ->and($this->service->getExamRooms(null))->toBe([]);
});

test('returns clinicians for organization', function () {
    $clinician = User::factory()->create();
    $admin = User::factory()->create();
    $receptionist = User::factory()->create();

    $this->organization->users()->attach($clinician->id, [
        'role' => OrganizationRole::Clinician->value,
        'joined_at' => now(),
    ]);
    $this->organization->users()->attach($admin->id, [
        'role' => OrganizationRole::Admin->value,
        'joined_at' => now(),
    ]);
    $this->organization->users()->attach($receptionist->id, [
        'role' => OrganizationRole::Receptionist->value,
        'joined_at' => now(),
    ]);

    $clinicians = $this->service->getClinicians($this->organization);

    expect($clinicians)->toBeArray()
        ->and($clinicians)->toHaveCount(2) // Clinician + Admin (not Receptionist)
        ->and(collect($clinicians)->pluck('id')->toArray())->toContain($clinician->id, $admin->id)
        ->and(collect($clinicians)->pluck('id')->toArray())->not->toContain($receptionist->id);
});

test('returns patients for organization ordered by last name', function () {
    $patient1 = Patient::factory()->for($this->organization)->create(['last_name' => 'Zebra']);
    $patient2 = Patient::factory()->for($this->organization)->create(['last_name' => 'Alpha']);
    $patient3 = Patient::factory()->for($this->organization)->create(['last_name' => 'Beta']);

    $patients = $this->service->getPatients($this->organization);

    expect($patients)->toBeArray()
        ->and($patients)->toHaveCount(3)
        ->and($patients[0]['id'])->toBe($patient2->id)
        ->and($patients[1]['id'])->toBe($patient3->id)
        ->and($patients[2]['id'])->toBe($patient1->id);
});

test('returns active exam rooms for organization ordered by room number', function () {
    $room1 = ExamRoom::factory()->for($this->organization)->create([
        'room_number' => '300',
        'is_active' => true,
    ]);
    $room2 = ExamRoom::factory()->for($this->organization)->create([
        'room_number' => '100',
        'is_active' => true,
    ]);
    $room3 = ExamRoom::factory()->for($this->organization)->create([
        'room_number' => '200',
        'is_active' => false, // Inactive
    ]);

    $rooms = $this->service->getExamRooms($this->organization);

    expect($rooms)->toBeArray()
        ->and($rooms)->toHaveCount(2) // Only active rooms
        ->and($rooms[0]['id'])->toBe($room2->id) // Ordered by room_number
        ->and($rooms[1]['id'])->toBe($room1->id)
        ->and(collect($rooms)->pluck('id')->toArray())->not->toContain($room3->id);
});

test('returns operating hours with defaults when not set', function () {
    $hours = $this->service->getOperatingHours($this->organization);

    expect($hours)->toBeArray()
        ->and($hours['startTime'])->toBe('08:00:00')
        ->and($hours['endTime'])->toBe('18:00:00');
});

test('returns configured operating hours when set', function () {
    $this->organization->update([
        'operating_hours_start' => '09:00:00',
        'operating_hours_end' => '17:00:00',
    ]);

    $hours = $this->service->getOperatingHours($this->organization);

    expect($hours)->toBeArray()
        ->and($hours['startTime'])->toBe('09:00:00')
        ->and($hours['endTime'])->toBe('17:00:00');
});

test('returns time slot interval with user preference when available', function () {
    $user = User::factory()->create([
        'current_organization_id' => $this->organization->id,
        'calendar_time_slot_interval' => 30,
    ]);

    $interval = $this->service->getTimeSlotInterval($this->organization, $user);

    expect($interval)->toBe(30);
});

test('returns organization default time slot interval when user preference is null', function () {
    $this->organization->update(['default_time_slot_interval' => 60]);
    $user = User::factory()->create([
        'current_organization_id' => $this->organization->id,
        'calendar_time_slot_interval' => null,
    ]);

    $interval = $this->service->getTimeSlotInterval($this->organization, $user);

    expect($interval)->toBe(60);
});

test('returns default time slot interval when both are null', function () {
    $user = User::factory()->create([
        'current_organization_id' => $this->organization->id,
        'calendar_time_slot_interval' => null,
    ]);

    $interval = $this->service->getTimeSlotInterval($this->organization, $user);

    expect($interval)->toBe(15); // Default fallback
});
