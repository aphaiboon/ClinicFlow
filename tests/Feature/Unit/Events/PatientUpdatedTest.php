<?php

use App\Events\PatientUpdated;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be instantiated with a patient', function () {
    $patient = Patient::factory()->create();
    $event = new PatientUpdated($patient);

    expect($event->patient)->toBe($patient);
});

it('contains the patient model', function () {
    $patient = Patient::factory()->create();
    $event = new PatientUpdated($patient);

    expect($event->patient->id)->toBe($patient->id);
});
