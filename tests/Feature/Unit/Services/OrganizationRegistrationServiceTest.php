<?php

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(OrganizationRegistrationService::class);
});

it('registers organization and user', function () {
    $organizationData = [
        'name' => 'Test Clinic',
        'email' => 'test@clinic.com',
        'phone' => '555-1234',
        'address_line_1' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701',
        'country' => 'US',
    ];

    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ];

    $organization = $this->service->register($organizationData, $userData);

    expect($organization)->toBeInstanceOf(Organization::class)
        ->and($organization->name)->toBe('Test Clinic');

    $user = User::where('email', 'john@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('John Doe')
        ->and(Hash::check('password123', $user->password))->toBeTrue()
        ->and($user->current_organization_id)->toBe($organization->id);
});

it('sets first user as owner', function () {
    $organizationData = ['name' => 'Test Clinic'];
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ];

    $organization = $this->service->register($organizationData, $userData);
    $user = User::where('email', 'john@example.com')->first();

    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
});

it('sets current organization for user', function () {
    $organizationData = ['name' => 'Test Clinic'];
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ];

    $organization = $this->service->register($organizationData, $userData);
    $user = User::where('email', 'john@example.com')->first();

    expect($user->current_organization_id)->toBe($organization->id);
});

it('validates required organization fields', function () {
    $organizationData = [];
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ];

    expect(fn () => $this->service->register($organizationData, $userData))
        ->toThrow();
});

it('validates required user fields', function () {
    $organizationData = ['name' => 'Test Clinic'];
    $userData = [];

    expect(fn () => $this->service->register($organizationData, $userData))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('accepts valid organization email format', function () {
    $organizationData = [
        'name' => 'Test Clinic',
        'email' => 'valid@clinic.com',
    ];
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ];

    $organization = $this->service->register($organizationData, $userData);

    expect($organization->email)->toBe('valid@clinic.com');
});

it('handles duplicate organization name by generating unique slug', function () {
    $organizationData1 = ['name' => 'Test Clinic'];
    $organizationData2 = ['name' => 'Test Clinic'];
    $userData1 = [
        'name' => 'John Doe',
        'email' => 'john1@example.com',
        'password' => 'password123',
    ];
    $userData2 = [
        'name' => 'Jane Doe',
        'email' => 'john2@example.com',
        'password' => 'password123',
    ];

    $org1 = $this->service->register($organizationData1, $userData1);
    $org2 = $this->service->register($organizationData2, $userData2);

    expect($org1->slug)->not->toBe($org2->slug);
});
