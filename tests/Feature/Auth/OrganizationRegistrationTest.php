<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('organization registration screen can be rendered', function () {
    $response = $this->get(route('organization.register'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('auth/organization-register')
    );
});

test('guest can register organization and user', function () {
    $data = [
        'name' => 'Test Clinic',
        'email' => 'test@clinic.com',
        'phone' => '555-1234',
        'address_line_1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701',
        'country' => 'US',
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->post(route('organization.register.store'), $data);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();

    $user = User::where('email', 'john@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->current_organization_id)->not->toBeNull();

    $organization = Organization::where('name', 'Test Clinic')->first();
    expect($organization)->not->toBeNull()
        ->and($user->current_organization_id)->toBe($organization->id);

    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
});

test('organization registration creates organization with provided details', function () {
    $data = [
        'name' => 'Test Clinic',
        'email' => 'test@clinic.com',
        'phone' => '555-1234',
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $this->post(route('organization.register.store'), $data);

    $this->assertDatabaseHas('organizations', [
        'name' => 'Test Clinic',
        'email' => 'test@clinic.com',
        'phone' => '555-1234',
    ]);
});

test('organization registration sets first user as owner', function () {
    $data = [
        'name' => 'Test Clinic',
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $this->post(route('organization.register.store'), $data);

    $user = User::where('email', 'john@example.com')->first();
    $organization = Organization::where('name', 'Test Clinic')->first();

    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
});

test('organization registration redirects to dashboard after registration', function () {
    $data = [
        'name' => 'Test Clinic',
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->post(route('organization.register.store'), $data);

    $response->assertRedirect(route('dashboard', absolute: false));
});

test('organization registration validates required organization fields', function () {
    $data = [
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->post(route('organization.register.store'), $data);

    $response->assertSessionHasErrors('name');
});

test('organization registration validates organization email format', function () {
    $data = [
        'name' => 'Test Clinic',
        'email' => 'invalid-email',
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->post(route('organization.register.store'), $data);

    $response->assertSessionHasErrors('email');
});

test('organization registration validates required user fields', function () {
    $data = ['name' => 'Test Clinic'];

    $response = $this->post(route('organization.register.store'), $data);

    $response->assertSessionHasErrors(['user_name', 'user_email', 'password']);
});

test('organization registration validates password confirmation', function () {
    $data = [
        'name' => 'Test Clinic',
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different',
    ];

    $response = $this->post(route('organization.register.store'), $data);

    $response->assertSessionHasErrors('password');
});

test('organization registration validates unique user email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $data = [
        'name' => 'Test Clinic',
        'user_name' => 'John Doe',
        'user_email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->post(route('organization.register.store'), $data);

    $response->assertSessionHasErrors('user_email');
});
