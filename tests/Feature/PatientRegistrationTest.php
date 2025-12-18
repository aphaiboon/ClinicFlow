<?php

use App\Enums\OrganizationRole;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'role' => UserRole::User,
        'current_organization_id' => $this->organization->id,
    ]);
    $this->organization->users()->attach($this->user->id, [
        'role' => OrganizationRole::Receptionist->value,
        'joined_at' => now(),
    ]);
});

it('completes full patient registration flow with audit logging', function () {
    $response = $this->actingAs($this->user)
        ->get('/patients/create');

    $response->assertSuccessful();

    $patientData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1980-01-15',
        'gender' => 'male',
        'phone' => '555-0100',
        'email' => 'john.doe@example.com',
        'address_line_1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701',
    ];

    $response = $this->actingAs($this->user)
        ->post('/patients', $patientData);

    $response->assertRedirect();

    $patient = Patient::where('email', 'john.doe@example.com')->first();

    expect($patient)->not->toBeNull()
        ->and($patient->first_name)->toBe('John')
        ->and($patient->last_name)->toBe('Doe')
        ->and($patient->medical_record_number)->not->toBeNull()
        ->and($patient->medical_record_number)->toStartWith('MRN-')
        ->and($patient->organization_id)->toBe($this->organization->id);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $this->user->id,
        'organization_id' => $this->organization->id,
        'action' => 'create',
        'resource_type' => 'Patient',
        'resource_id' => $patient->id,
    ]);
});

it('prevents patient registration with invalid data', function () {
    $invalidData = [
        'first_name' => '',
        'last_name' => 'Doe',
        'date_of_birth' => '2030-01-01',
        'gender' => 'invalid',
    ];

    $response = $this->actingAs($this->user)
        ->post('/patients', $invalidData);

    $response->assertSessionHasErrors(['first_name', 'date_of_birth', 'gender']);

    expect(Patient::count())->toBe(0);
});
