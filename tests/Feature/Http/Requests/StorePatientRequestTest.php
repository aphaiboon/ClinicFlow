<?php

use App\Enums\Gender;
use App\Enums\UserRole;
use App\Http\Requests\StorePatientRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => UserRole::Receptionist]);
    $this->request = new StorePatientRequest;
    $this->request->setUserResolver(fn () => $this->user);
});

it('passes validation with valid data', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
        'gender' => Gender::Male->value,
        'phone' => '555-1234',
        'email' => 'john@example.com',
        'address_line_1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701',
        'country' => 'US',
    ];

    $validator = Validator::make($data, $this->request->rules());

    expect($validator->passes())->toBeTrue();
});

it('requires first_name', function () {
    $data = [
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
    ];

    $validator = Validator::make($data, $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('first_name'))->toBeTrue();
});

it('requires last_name', function () {
    $data = [
        'first_name' => 'John',
        'date_of_birth' => '1990-01-15',
    ];

    $validator = Validator::make($data, $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('last_name'))->toBeTrue();
});

it('requires date_of_birth', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
    ];

    $validator = Validator::make($data, $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('date_of_birth'))->toBeTrue();
});

it('validates date_of_birth is in the past', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => now()->addDay()->format('Y-m-d'),
    ];

    $validator = Validator::make($data, $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('date_of_birth'))->toBeTrue();
});

it('validates gender is a valid enum value', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
        'gender' => 'invalid',
    ];

    $validator = Validator::make($data, $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('gender'))->toBeTrue();
});

it('validates email format when provided', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
        'email' => 'invalid-email',
    ];

    $validator = Validator::make($data, $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
});

it('allows email to be nullable', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
        'gender' => Gender::Male->value,
        'phone' => '555-1234',
        'address_line_1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701',
    ];

    $validator = Validator::make($data, $this->request->rules());

    expect($validator->passes())->toBeTrue();
});

it('requires address_line_1', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
        'city' => 'Springfield',
    ];

    $validator = Validator::make($data, $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('address_line_1'))->toBeTrue();
});

it('requires city', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
        'address_line_1' => '123 Main St',
    ];

    $validator = Validator::make($data, $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('city'))->toBeTrue();
});

it('requires state', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
        'address_line_1' => '123 Main St',
        'city' => 'Springfield',
    ];

    $validator = Validator::make($data, $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('state'))->toBeTrue();
});

it('requires postal_code', function () {
    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1990-01-15',
        'address_line_1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
    ];

    $validator = Validator::make($data, $this->request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('postal_code'))->toBeTrue();
});

it('authorizes receptionist to create patients', function () {
    $user = User::factory()->create(['role' => UserRole::Receptionist]);
    $request = new StorePatientRequest;
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeTrue();
});

it('authorizes admin to create patients', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);
    $request = new StorePatientRequest;
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeTrue();
});

it('prevents clinician from creating patients', function () {
    $user = User::factory()->create(['role' => UserRole::Clinician]);
    $request = new StorePatientRequest;
    $request->setUserResolver(fn () => $user);

    expect($request->authorize())->toBeFalse();
});
