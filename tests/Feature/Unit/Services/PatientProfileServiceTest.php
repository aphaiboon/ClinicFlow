<?php

use App\Models\Organization;
use App\Models\Patient;
use App\Services\PatientProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create(['name' => 'ABC Clinic']);
    $this->patient = Patient::factory()->for($this->organization)->create([
        'email' => 'patient@abc-clinic.test',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone' => '555-1234',
        'address_line_1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701',
    ]);
    $this->service = app(PatientProfileService::class);
});

it('returns list of editable fields', function () {
    $editableFields = $this->service->getEditableFields();

    expect($editableFields)->toBeArray()
        ->and($editableFields)->toContain('phone', 'email', 'address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country')
        ->and($editableFields)->not->toContain('medical_record_number', 'date_of_birth', 'first_name', 'last_name', 'organization_id');
});

it('can update patient profile with valid editable fields', function () {
    $updateData = [
        'phone' => '555-9999',
        'email' => 'newemail@abc-clinic.test',
        'address_line_1' => '456 New St',
        'city' => 'Chicago',
        'state' => 'IL',
        'postal_code' => '60601',
    ];

    $updated = $this->service->updatePatientProfile($this->patient, $updateData);

    expect($updated->phone)->toBe('555-9999')
        ->and($updated->email)->toBe('newemail@abc-clinic.test')
        ->and($updated->address_line_1)->toBe('456 New St')
        ->and($updated->city)->toBe('Chicago');
});

it('cannot update immutable fields', function () {
    $originalMrn = $this->patient->medical_record_number;
    $originalDob = $this->patient->date_of_birth;
    $originalFirstName = $this->patient->first_name;

    $updateData = [
        'medical_record_number' => 'MRN-99999999',
        'date_of_birth' => '2000-01-01',
        'first_name' => 'Jane',
        'phone' => '555-9999',
    ];

    $updated = $this->service->updatePatientProfile($this->patient, $updateData);

    expect($updated->medical_record_number)->toBe($originalMrn)
        ->and($updated->date_of_birth->format('Y-m-d'))->toBe($originalDob->format('Y-m-d'))
        ->and($updated->first_name)->toBe($originalFirstName)
        ->and($updated->phone)->toBe('555-9999'); // Editable field was updated
});

it('validates email format', function () {
    $updateData = [
        'email' => 'invalid-email',
    ];

    expect(fn () => $this->service->updatePatientProfile($this->patient, $updateData))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('validates phone format', function () {
    $updateData = [
        'phone' => '', // Empty phone not allowed
    ];

    expect(fn () => $this->service->updatePatientProfile($this->patient, $updateData))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});
