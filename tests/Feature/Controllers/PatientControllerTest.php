<?php

use App\Enums\UserRole;
use App\Models\Patient;
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

it('requires authentication to view patients index', function () {
    $response = $this->get('/patients');

    $response->assertRedirect(route('login'));
});

it('displays patients index for authenticated users', function () {
    Patient::factory()->for($this->organization)->count(5)->create();

    $response = $this->actingAs($this->receptionist)->get('/patients');

    $response->assertSuccessful();
});

it('allows admin to view patients index', function () {
    Patient::factory()->for($this->organization)->count(3)->create();

    $response = $this->actingAs($this->admin)->get('/patients');

    $response->assertSuccessful();
});

it('allows receptionist to view patients index', function () {
    Patient::factory()->for($this->organization)->count(3)->create();

    $response = $this->actingAs($this->receptionist)->get('/patients');

    $response->assertSuccessful();
});

it('allows clinician to view patients index', function () {
    Patient::factory()->for($this->organization)->count(3)->create();

    $response = $this->actingAs($this->clinician)->get('/patients');

    $response->assertSuccessful();
});

it('can search patients by name', function () {
    Patient::factory()->for($this->organization)->create(['first_name' => 'John', 'last_name' => 'Doe']);
    Patient::factory()->for($this->organization)->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

    $response = $this->actingAs($this->receptionist)->get('/patients?search=John');

    $response->assertSuccessful();
});

it('displays create patient form for authorized users', function () {
    $response = $this->actingAs($this->receptionist)->get('/patients/create');

    $response->assertSuccessful();
});

it('prevents clinician from accessing create patient form', function () {
    $response = $this->actingAs($this->clinician)->get('/patients/create');

    $response->assertForbidden();
});

it('allows receptionist to create a patient', function () {
    $patientData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
        'gender' => 'male',
        'phone' => '555-1234',
        'email' => 'john@example.com',
        'address_line_1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701',
    ];

    $response = $this->actingAs($this->receptionist)->post('/patients', $patientData);

    $response->assertRedirect();
    $this->assertDatabaseHas('patients', ['first_name' => 'John', 'last_name' => 'Doe']);
});

it('prevents clinician from creating a patient', function () {
    $patientData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
        'gender' => 'male',
    ];

    $response = $this->actingAs($this->clinician)->post('/patients', $patientData);

    $response->assertForbidden();
});

it('validates required fields when creating patient', function () {
    $response = $this->actingAs($this->receptionist)->post('/patients', []);

    $response->assertSessionHasErrors(['first_name', 'last_name', 'date_of_birth', 'gender']);
});

it('displays patient details', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    $response = $this->actingAs($this->receptionist)->get("/patients/{$patient->id}");

    $response->assertSuccessful();
});

it('displays edit patient form for authorized users', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    $response = $this->actingAs($this->receptionist)->get("/patients/{$patient->id}/edit");

    $response->assertSuccessful();
});

it('allows receptionist to update a patient', function () {
    $patient = Patient::factory()->for($this->organization)->create(['first_name' => 'John']);

    $response = $this->actingAs($this->receptionist)
        ->put("/patients/{$patient->id}", ['first_name' => 'Jane']);

    $response->assertRedirect();
    $this->assertDatabaseHas('patients', ['id' => $patient->id, 'first_name' => 'Jane']);
});

it('prevents clinician from updating a patient', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    $response = $this->actingAs($this->clinician)
        ->put("/patients/{$patient->id}", ['first_name' => 'Jane']);

    $response->assertForbidden();
});

it('allows admin to delete a patient', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    $response = $this->actingAs($this->admin)->delete("/patients/{$patient->id}");

    $response->assertRedirect();
    $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
});

it('prevents receptionist from deleting a patient', function () {
    $patient = Patient::factory()->for($this->organization)->create();

    $response = $this->actingAs($this->receptionist)->delete("/patients/{$patient->id}");

    $response->assertForbidden();
});
