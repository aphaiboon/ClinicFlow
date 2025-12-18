<?php

use App\Enums\Gender;
use App\Enums\UserRole;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = \App\Models\Organization::factory()->create();
    $this->user = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $this->organization->id]);
    $this->organization->users()->attach($this->user->id, ['role' => \App\Enums\OrganizationRole::Receptionist->value, 'joined_at' => now()]);
    $this->patient = Patient::factory()->for($this->organization)->create();
});

it('passes validation with valid data', function () {
    $request = new UpdatePatientRequest;
    $data = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'date_of_birth' => '1985-05-20',
        'gender' => Gender::Female->value,
        'email' => 'jane@example.com',
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

it('allows all fields to be optional', function () {
    $request = new UpdatePatientRequest;
    $data = [];

    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

it('validates date_of_birth is in the past when provided', function () {
    $request = new UpdatePatientRequest;
    $data = [
        'date_of_birth' => now()->addDay()->format('Y-m-d'),
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('date_of_birth'))->toBeTrue();
});

it('validates email format when provided', function () {
    $request = new UpdatePatientRequest;
    $data = [
        'email' => 'invalid-email',
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
});

it('authorizes receptionist to update patients', function () {
    $organization = \App\Models\Organization::factory()->create();
    $user = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $organization->id]);
    $organization->users()->attach($user->id, ['role' => \App\Enums\OrganizationRole::Receptionist->value, 'joined_at' => now()]);
    $patient = Patient::factory()->for($organization)->create();

    $request = UpdatePatientRequest::create('/patients/'.$patient->id, 'PUT', []);
    $request->setUserResolver(fn () => $user);

    $route = new \Illuminate\Routing\Route(['PUT'], '/patients/{patient}', fn () => null);
    $route->parameters = ['patient' => $patient];
    $request->setRouteResolver(fn () => $route);

    expect($request->authorize())->toBeTrue();
});

it('authorizes admin to update patients', function () {
    $organization = \App\Models\Organization::factory()->create();
    $user = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $organization->id]);
    $organization->users()->attach($user->id, ['role' => \App\Enums\OrganizationRole::Admin->value, 'joined_at' => now()]);
    $patient = Patient::factory()->for($organization)->create();

    $request = UpdatePatientRequest::create('/patients/'.$patient->id, 'PUT', []);
    $request->setUserResolver(fn () => $user);

    $route = new \Illuminate\Routing\Route(['PUT'], '/patients/{patient}', fn () => null);
    $route->parameters = ['patient' => $patient];
    $request->setRouteResolver(fn () => $route);

    expect($request->authorize())->toBeTrue();
});

it('prevents clinician from updating patients', function () {
    $organization = \App\Models\Organization::factory()->create();
    $user = User::factory()->create(['role' => UserRole::User, 'current_organization_id' => $organization->id]);
    $organization->users()->attach($user->id, ['role' => \App\Enums\OrganizationRole::Clinician->value, 'joined_at' => now()]);
    $patient = Patient::factory()->for($organization)->create();

    $request = UpdatePatientRequest::create('/patients/'.$patient->id, 'PUT', []);
    $request->setUserResolver(fn () => $user);

    $route = new \Illuminate\Routing\Route(['PUT'], '/patients/{patient}', fn () => null);
    $route->parameters = ['patient' => $patient];
    $request->setRouteResolver(fn () => $route);

    expect($request->authorize())->toBeFalse();
});
