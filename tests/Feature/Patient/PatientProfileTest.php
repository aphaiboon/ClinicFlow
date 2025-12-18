<?php

use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create(['name' => 'ABC Clinic']);
    $this->patient = Patient::factory()->for($this->organization)->create([
        'email' => 'patient@abc-clinic.test',
        'phone' => '123-456-7890',
        'address_line_1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701',
        'country' => 'US',
    ]);
    $this->otherPatient = Patient::factory()->for($this->organization)->create();
});

test('patient can view their own profile', function () {
    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.profile.show'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Patient/Profile/Show')
            ->where('patient.id', $this->patient->id)
            ->where('patient.email', $this->patient->email)
        );
});

test('patient can access profile edit page', function () {
    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.profile.edit'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Patient/Profile/Edit')
            ->where('patient.id', $this->patient->id)
            ->has('editableFields')
        );
});

test('patient can update editable fields', function () {
    $updateData = [
        'email' => 'newemail@example.com',
        'phone' => '987-654-3210',
        'address_line_1' => '456 Oak Ave',
        'city' => 'Chicago',
        'state' => 'IL',
        'postal_code' => '60601',
        'country' => 'US',
    ];

    $response = $this->actingAs($this->patient, 'patient')
        ->put(route('patient.profile.update'), $updateData);

    $response->assertRedirect(route('patient.profile.show'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('patients', [
        'id' => $this->patient->id,
        'email' => 'newemail@example.com',
        'phone' => '987-654-3210',
        'address_line_1' => '456 Oak Ave',
        'city' => 'Chicago',
    ]);
});

test('patient cannot update immutable fields', function () {
    $originalMrn = $this->patient->medical_record_number;
    $originalDob = $this->patient->date_of_birth;
    $originalFirstName = $this->patient->first_name;
    $originalLastName = $this->patient->last_name;

    $updateData = [
        'medical_record_number' => 'MRN-NEW',
        'date_of_birth' => '2000-01-01',
        'first_name' => 'NewFirst',
        'last_name' => 'NewLast',
        'phone' => '987-654-3210',
    ];

    $this->actingAs($this->patient, 'patient')
        ->put(route('patient.profile.update'), $updateData);

    $this->assertDatabaseHas('patients', [
        'id' => $this->patient->id,
        'medical_record_number' => $originalMrn,
        'first_name' => $originalFirstName,
        'last_name' => $originalLastName,
        'phone' => '987-654-3210',
    ]);

    $patient = $this->patient->fresh();
    expect($patient->date_of_birth->format('Y-m-d'))->toBe($originalDob->format('Y-m-d'));
});

test('profile update validates email format', function () {
    $response = $this->actingAs($this->patient, 'patient')
        ->put(route('patient.profile.update'), [
            'email' => 'invalid-email',
        ]);

    $response->assertSessionHasErrors('email');
});

test('profile update validates phone format', function () {
    $response = $this->actingAs($this->patient, 'patient')
        ->put(route('patient.profile.update'), [
            'phone' => '123',
        ]);

    $response->assertSessionHasErrors('phone');
});

test('profile update validates address fields', function () {
    $response = $this->actingAs($this->patient, 'patient')
        ->put(route('patient.profile.update'), [
            'address_line_1' => '',
            'city' => '',
            'state' => '',
            'postal_code' => '',
        ]);

    $response->assertSessionHasErrors(['address_line_1', 'city', 'state', 'postal_code']);
});

test('profile update validates unique email', function () {
    Patient::factory()->for($this->organization)->create([
        'email' => 'existing@example.com',
    ]);

    $response = $this->actingAs($this->patient, 'patient')
        ->put(route('patient.profile.update'), [
            'email' => 'existing@example.com',
        ]);

    $response->assertSessionHasErrors('email');
});

test('profile update allows patient to update their own email to the same email', function () {
    $response = $this->actingAs($this->patient, 'patient')
        ->put(route('patient.profile.update'), [
            'email' => $this->patient->email,
        ]);

    $response->assertRedirect(route('patient.profile.show'));
    $response->assertSessionHas('success');
});

test('profile update creates audit log', function () {
    $this->actingAs($this->patient, 'patient')
        ->put(route('patient.profile.update'), [
            'phone' => '987-654-3210',
        ]);

    $this->assertDatabaseHas('audit_logs', [
        'resource_type' => 'Patient',
        'resource_id' => $this->patient->id,
        'action' => 'update',
        'metadata->patient_id' => $this->patient->id,
    ]);
});

test('patient cannot view other patients profiles', function () {
    $response = $this->actingAs($this->patient, 'patient')
        ->get(route('patient.profile.show'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('patient.id', $this->patient->id)
            ->where('patient.id', fn ($id) => $id !== $this->otherPatient->id)
        );
});

test('patient cannot update other patients profiles', function () {
    $originalOtherPatientEmail = $this->otherPatient->email;

    $response = $this->actingAs($this->patient, 'patient')
        ->put(route('patient.profile.update'), [
            'email' => 'hacked@example.com',
        ]);

    $response->assertRedirect(route('patient.profile.show'));

    $this->assertDatabaseHas('patients', [
        'id' => $this->otherPatient->id,
        'email' => $originalOtherPatientEmail,
    ]);

    $this->assertDatabaseMissing('patients', [
        'id' => $this->otherPatient->id,
        'email' => 'hacked@example.com',
    ]);
});

test('unauthenticated users cannot access profile routes', function () {
    $response1 = $this->get(route('patient.profile.show'));
    $response1->assertRedirect(route('patient.login'));

    $response2 = $this->get(route('patient.profile.edit'));
    $response2->assertRedirect(route('patient.login'));

    $response3 = $this->put(route('patient.profile.update'), []);
    $response3->assertRedirect(route('patient.login'));
});

test('staff cannot access patient profile routes', function () {
    $user = User::factory()->create();

    $response1 = $this->actingAs($user)->get(route('patient.profile.show'));
    $response1->assertRedirect(route('login'));

    $response2 = $this->actingAs($user)->get(route('patient.profile.edit'));
    $response2->assertRedirect(route('login'));

    $response3 = $this->actingAs($user)->put(route('patient.profile.update'), []);
    $response3->assertRedirect(route('login'));
});

test('profile update can update partial fields', function () {
    $originalEmail = $this->patient->email;

    $response = $this->actingAs($this->patient, 'patient')
        ->put(route('patient.profile.update'), [
            'phone' => '555-1234',
        ]);

    $response->assertRedirect(route('patient.profile.show'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('patients', [
        'id' => $this->patient->id,
        'phone' => '555-1234',
        'email' => $originalEmail,
    ]);
});
