<?php

use App\Enums\UserRole;
use App\Models\ExamRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = \App\Models\Organization::factory()->create();
    $this->admin = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->receptionist = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->clinician = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->admin->id, ['role' => \App\Enums\OrganizationRole::Admin->value, 'joined_at' => now()]);
    $this->organization->users()->attach($this->receptionist->id, ['role' => \App\Enums\OrganizationRole::Receptionist->value, 'joined_at' => now()]);
    $this->organization->users()->attach($this->clinician->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
});

it('requires authentication to view exam rooms index', function () {
    $response = $this->get('/exam-rooms');

    $response->assertRedirect(route('login'));
});

it('displays exam rooms index for authenticated users', function () {
    ExamRoom::factory()->for($this->organization)->count(5)->create();

    $response = $this->actingAs($this->receptionist)->get('/exam-rooms');

    $response->assertSuccessful();
});

it('allows admin to view exam rooms index', function () {
    ExamRoom::factory()->for($this->organization)->count(3)->create();

    $response = $this->actingAs($this->admin)->get('/exam-rooms');

    $response->assertSuccessful();
});

it('allows receptionist to view exam rooms index', function () {
    ExamRoom::factory()->for($this->organization)->count(3)->create();

    $response = $this->actingAs($this->receptionist)->get('/exam-rooms');

    $response->assertSuccessful();
});

it('allows clinician to view exam rooms index', function () {
    ExamRoom::factory()->for($this->organization)->count(3)->create();

    $response = $this->actingAs($this->clinician)->get('/exam-rooms');

    $response->assertSuccessful();
});

it('displays create exam room form for admin', function () {
    $response = $this->actingAs($this->admin)->get('/exam-rooms/create');

    $response->assertSuccessful();
});

it('prevents non-admin from accessing create exam room form', function () {
    $response = $this->actingAs($this->receptionist)->get('/exam-rooms/create');

    $response->assertForbidden();
});

it('allows admin to create an exam room', function () {
    $roomData = [
        'room_number' => '101',
        'name' => 'Exam Room 101',
        'floor' => 1,
        'capacity' => 2,
        'is_active' => true,
    ];

    $response = $this->actingAs($this->admin)->post('/exam-rooms', $roomData);

    $response->assertRedirect();
    $this->assertDatabaseHas('exam_rooms', ['room_number' => '101']);
});

it('prevents non-admin from creating exam rooms', function () {
    $roomData = [
        'room_number' => '101',
        'name' => 'Exam Room 101',
        'floor' => 1,
        'capacity' => 2,
    ];

    $response = $this->actingAs($this->receptionist)->post('/exam-rooms', $roomData);

    $response->assertForbidden();
});

it('validates required fields when creating exam room', function () {
    $response = $this->actingAs($this->admin)->post('/exam-rooms', []);

    $response->assertSessionHasErrors(['room_number', 'name', 'capacity']);
});

it('displays exam room details', function () {
    $room = ExamRoom::factory()->for($this->organization)->create();

    $response = $this->actingAs($this->receptionist)->get("/exam-rooms/{$room->id}");

    $response->assertSuccessful();
});

it('allows admin to update an exam room', function () {
    $room = ExamRoom::factory()->for($this->organization)->create(['name' => 'Original Name']);

    $response = $this->actingAs($this->admin)
        ->put("/exam-rooms/{$room->id}", ['name' => 'Updated Name']);

    $response->assertRedirect();
    $this->assertDatabaseHas('exam_rooms', ['id' => $room->id, 'name' => 'Updated Name']);
});

it('prevents non-admin from updating exam rooms', function () {
    $room = ExamRoom::factory()->for($this->organization)->create();

    $response = $this->actingAs($this->receptionist)
        ->put("/exam-rooms/{$room->id}", ['name' => 'Updated Name']);

    $response->assertForbidden();
});

it('allows admin to activate an exam room', function () {
    $room = ExamRoom::factory()->for($this->organization)->create(['is_active' => false]);

    $response = $this->actingAs($this->admin)
        ->post("/exam-rooms/{$room->id}/activate");

    $response->assertRedirect();
    $this->assertDatabaseHas('exam_rooms', ['id' => $room->id, 'is_active' => true]);
});

it('prevents non-admin from activating exam rooms', function () {
    $room = ExamRoom::factory()->for($this->organization)->create(['is_active' => false]);

    $response = $this->actingAs($this->receptionist)
        ->post("/exam-rooms/{$room->id}/activate");

    $response->assertForbidden();
});

it('allows admin to deactivate an exam room', function () {
    $room = ExamRoom::factory()->for($this->organization)->create(['is_active' => true]);

    $response = $this->actingAs($this->admin)
        ->post("/exam-rooms/{$room->id}/deactivate");

    $response->assertRedirect();
    $this->assertDatabaseHas('exam_rooms', ['id' => $room->id, 'is_active' => false]);
});

it('prevents non-admin from deactivating exam rooms', function () {
    $room = ExamRoom::factory()->for($this->organization)->create(['is_active' => true]);

    $response = $this->actingAs($this->receptionist)
        ->post("/exam-rooms/{$room->id}/deactivate");

    $response->assertForbidden();
});
