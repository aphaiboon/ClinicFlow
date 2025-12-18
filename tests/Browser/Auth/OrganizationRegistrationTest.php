<?php

use App\Models\Organization;
use App\Models\User;

it('can visit the organization registration page', function () {
    $page = visit('/register');

    $page->assertSee('Create your clinic organization')
        ->assertSee('Clinic Information')
        ->assertSee('Your Account');
});

it('can register organization and user successfully', function () {
    $page = visit('/register');

    $page->type('name', 'Test Clinic')
        ->type('email', 'test@clinic.com')
        ->type('phone', '555-1234')
        ->type('user_name', 'John Doe')
        ->type('user_email', 'john@example.com')
        ->type('password', 'password123')
        ->type('password_confirmation', 'password123')
        ->press('Create Organization')
        ->assertPathIs('/dashboard');

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

it('shows validation errors for missing required fields', function () {
    $page = visit('/register');

    $page->press('Create Organization')
        ->waitForText('Clinic Name', 5)
        ->assertPathIs('/register');
});

it('shows validation error for password mismatch', function () {
    $page = visit('/register');

    $page->type('name', 'Test Clinic')
        ->type('user_name', 'John Doe')
        ->type('user_email', 'john@example.com')
        ->type('password', 'password123')
        ->type('password_confirmation', 'different')
        ->press('Create Organization')
        ->waitForText('Clinic Name', 5)
        ->assertPathIs('/register');
});

it('shows validation error for invalid email format', function () {
    $page = visit('/register');

    $page->type('name', 'Test Clinic')
        ->type('email', 'invalid-email')
        ->type('user_name', 'John Doe')
        ->type('user_email', 'john@example.com')
        ->type('password', 'password123')
        ->type('password_confirmation', 'password123')
        ->press('Create Organization')
        ->waitForText('Clinic Name', 5)
        ->assertPathIs('/register');
});

it('shows validation error for duplicate user email', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $page = visit('/register');

    $page->type('name', 'Test Clinic')
        ->type('user_name', 'John Doe')
        ->type('user_email', 'existing@example.com')
        ->type('password', 'password123')
        ->type('password_confirmation', 'password123')
        ->press('Create Organization')
        ->waitForText('Clinic Name', 5)
        ->assertPathIs('/register');
});
