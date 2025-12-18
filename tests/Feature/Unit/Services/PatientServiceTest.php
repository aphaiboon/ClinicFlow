<?php

use App\Models\Patient;
use App\Models\User;
use App\Services\PatientService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = \App\Models\Organization::factory()->create();
    $this->user = User::factory()->create(['current_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->user->id, ['role' => \App\Enums\OrganizationRole::Admin->value, 'joined_at' => now()]);
    $this->actingAs($this->user);
    $this->service = app(PatientService::class);
});

it('can create a patient with generated medical record number', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
        'gender' => 'male',
        'phone' => '555-1234',
        'address_line_1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701',
        'country' => 'US',
    ];

    $patient = $this->service->createPatient($data);

    expect($patient)->toBeInstanceOf(Patient::class)
        ->and($patient->medical_record_number)->not->toBeEmpty()
        ->and($patient->medical_record_number)->toStartWith('MRN-')
        ->and($patient->first_name)->toBe('John')
        ->and($patient->last_name)->toBe('Doe');

    $this->assertDatabaseHas('patients', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'medical_record_number' => $patient->medical_record_number,
    ]);
});

it('generates unique medical record numbers', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
        'gender' => 'male',
        'phone' => '555-1234',
        'address_line_1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701',
        'country' => 'US',
    ];

    $patient1 = $this->service->createPatient($data);
    $data['first_name'] = 'Jane';
    $patient2 = $this->service->createPatient($data);

    expect($patient1->medical_record_number)->not->toBe($patient2->medical_record_number);
});

it('creates audit log when patient is created', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
        'gender' => 'male',
        'phone' => '555-1234',
        'address_line_1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701',
        'country' => 'US',
    ];

    $patient = $this->service->createPatient($data);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $this->user->id,
        'action' => 'create',
        'resource_type' => 'Patient',
        'resource_id' => $patient->id,
    ]);
});

it('can update a patient', function () {
    $patient = Patient::factory()->for($this->organization)->create();
    $originalMrn = $patient->medical_record_number;

    $updateData = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ];

    $updated = $this->service->updatePatient($patient, $updateData);

    expect($updated->first_name)->toBe('Jane')
        ->and($updated->last_name)->toBe('Smith')
        ->and($updated->medical_record_number)->toBe($originalMrn);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'update',
        'resource_type' => 'Patient',
        'resource_id' => $patient->id,
    ]);
});

it('can find a patient by id', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    $found = $this->service->findPatient($patient->id);

    expect($found)->toBeInstanceOf(Patient::class)
        ->and($found->id)->toBe($patient->id);
});

it('returns null when patient not found', function () {
    $found = $this->service->findPatient(99999);

    expect($found)->toBeNull();
});

it('can search patients by name', function () {
    Patient::factory()->for($this->organization)->create(['first_name' => 'John', 'last_name' => 'Doe']);
    Patient::factory()->for($this->organization)->create(['first_name' => 'Jane', 'last_name' => 'Smith']);
    Patient::factory()->for($this->organization)->create(['first_name' => 'Bob', 'last_name' => 'Johnson']);

    $results = $this->service->searchPatients('John');

    expect($results)->toHaveCount(2)
        ->and($results->pluck('last_name')->toArray())->toContain('Doe', 'Johnson');
});
