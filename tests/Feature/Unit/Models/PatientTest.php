<?php

use App\Enums\Gender;
use App\Models\Appointment;
use App\Models\Patient;

it('has fillable attributes', function () {
    $patient = new Patient;
    $fillable = [
        'medical_record_number',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
    ];

    expect($patient->getFillable())->toBe($fillable);
});

it('casts gender to enum', function () {
    $patient = Patient::factory()->create(['gender' => Gender::Male]);

    expect($patient->gender)->toBe(Gender::Male)
        ->and($patient->getAttributes()['gender'])->toBe('male');
});

it('casts date_of_birth to date', function () {
    $patient = Patient::factory()->create(['date_of_birth' => '1990-01-15']);

    expect($patient->date_of_birth)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('has appointments relationship', function () {
    $patient = Patient::factory()->create();
    Appointment::factory()->count(3)->create(['patient_id' => $patient->id]);

    expect($patient->appointments)->toHaveCount(3)
        ->and($patient->appointments->first())->toBeInstanceOf(Appointment::class);
});

it('can search patients by name', function () {
    Patient::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
    Patient::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);
    Patient::factory()->create(['first_name' => 'Bob', 'last_name' => 'Johnson']);

    $results = Patient::searchByName('John')->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('last_name')->toArray())->toContain('Doe', 'Johnson');
});

it('can search patients by full name', function () {
    Patient::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

    $results = Patient::searchByName('John Doe')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->first_name)->toBe('John');
});
