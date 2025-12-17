<?php

use App\Events\PatientCreated;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be instantiated with a patient', function () {
    $patient = Patient::factory()->create();
    $event = new PatientCreated($patient);

    expect($event->patient)->toBe($patient);
});

it('contains the patient model', function () {
    $patient = Patient::factory()->create();
    $event = new PatientCreated($patient);

    expect($event->patient->id)->toBe($patient->id)
        ->and($event->patient->first_name)->toBe($patient->first_name);
});
