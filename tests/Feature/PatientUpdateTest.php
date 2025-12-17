<?php

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('completes full patient update flow with audit logging', function () {
    $user = User::factory()->create(['role' => UserRole::Receptionist]);
    $patient = Patient::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
    ]);

    $originalEmail = $patient->email;

    $response = $this->actingAs($user)
        ->get("/patients/{$patient->id}/edit");

    $response->assertSuccessful();

    $updateData = [
        'first_name' => 'John',
        'last_name' => 'Smith',
        'email' => 'john.smith@example.com',
        'phone' => '555-0200',
        'date_of_birth' => $patient->date_of_birth->toDateString(),
        'gender' => $patient->gender->value,
        'address_line_1' => $patient->address_line_1,
        'city' => $patient->city,
        'state' => $patient->state,
        'postal_code' => $patient->postal_code,
    ];

    $response = $this->actingAs($user)
        ->put("/patients/{$patient->id}", $updateData);

    $response->assertRedirect();

    $patient->refresh();

    expect($patient->last_name)->toBe('Smith')
        ->and($patient->email)->toBe('john.smith@example.com')
        ->and($patient->phone)->toBe('555-0200');

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'update',
        'resource_type' => 'App\\Models\\Patient',
        'resource_id' => $patient->id,
    ]);
});

it('tracks changes correctly in audit log during patient update', function () {
    $user = User::factory()->create(['role' => UserRole::Receptionist]);
    $patient = Patient::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);

    $updateData = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'phone' => '555-0300',
        'date_of_birth' => $patient->date_of_birth->toDateString(),
        'gender' => $patient->gender->value,
        'address_line_1' => $patient->address_line_1,
        'city' => $patient->city,
        'state' => $patient->state,
        'postal_code' => $patient->postal_code,
    ];

    $this->actingAs($user)
        ->put("/patients/{$patient->id}", $updateData);

    $auditLog = \App\Models\AuditLog::where('resource_type', 'App\\Models\\Patient')
        ->where('resource_id', $patient->id)
        ->where('action', 'update')
        ->latest()
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->changes)->toHaveKey('after');
});
