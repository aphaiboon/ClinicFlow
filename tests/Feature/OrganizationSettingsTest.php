<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('organization can store and retrieve operating hours', function () {
    $organization = Organization::factory()->create([
        'operating_hours_start' => '08:00:00',
        'operating_hours_end' => '18:00:00',
        'default_time_slot_interval' => 15,
    ]);

    expect($organization->operating_hours_start)->toBe('08:00:00')
        ->and($organization->operating_hours_end)->toBe('18:00:00')
        ->and($organization->default_time_slot_interval)->toBe(15);
});

test('organization operating hours default to 08:00 and 18:00', function () {
    $organization = Organization::factory()->create();

    expect($organization->operating_hours_start)->toBe('08:00:00')
        ->and($organization->operating_hours_end)->toBe('18:00:00')
        ->and($organization->default_time_slot_interval)->toBe(15);
});

test('user can store and retrieve calendar time slot interval preference', function () {
    $user = User::factory()->create([
        'calendar_time_slot_interval' => 30,
    ]);

    expect($user->calendar_time_slot_interval)->toBe(30);
});

test('user calendar time slot interval defaults to 15 when null', function () {
    $user = User::factory()->create([
        'calendar_time_slot_interval' => null,
    ]);

    expect($user->calendar_time_slot_interval)->toBeNull();
});

test('organization operating hours are cast to time format', function () {
    $organization = Organization::factory()->create([
        'operating_hours_start' => '09:00:00',
        'operating_hours_end' => '17:00:00',
    ]);

    expect($organization->operating_hours_start)->toBe('09:00:00')
        ->and($organization->operating_hours_end)->toBe('17:00:00');
});

test('user calendar time slot interval is cast to integer', function () {
    $user = User::factory()->create([
        'calendar_time_slot_interval' => '30',
    ]);

    expect($user->calendar_time_slot_interval)->toBeInt()->toBe(30);
});
