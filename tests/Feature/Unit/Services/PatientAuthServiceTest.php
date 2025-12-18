<?php

use App\Models\Patient;
use App\Services\PatientAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->organization = \App\Models\Organization::factory()->create(['name' => 'ABC Clinic']);
    $this->service = app(PatientAuthService::class);
    Cache::flush();
});

it('generates a secure magic link token', function () {
    $patient = Patient::factory()->for($this->organization)->create([
        'email' => 'patient@abc-clinic.test',
    ]);

    $token = $this->service->generateMagicLinkToken($patient);

    expect($token)
        ->toBeString()
        ->not->toBeEmpty()
        ->and(strlen($token))->toBeGreaterThan(32);
});

it('stores token in cache with expiration', function () {
    Notification::fake();

    $patient = Patient::factory()->for($this->organization)->create([
        'email' => 'patient@abc-clinic.test',
    ]);

    $this->service->sendMagicLink('patient@abc-clinic.test');

    // Get the token from the notification
    Notification::assertSentTo($patient, \App\Notifications\PatientMagicLinkNotification::class, function ($notification) use ($patient) {
        $token = $notification->getToken();
        $cachedPatientId = Cache::get("patient_magic_link:{$token}");

        expect($cachedPatientId)->toBe($patient->id);

        return true;
    });
});

it('sends magic link email to patient', function () {
    Notification::fake();

    $patient = Patient::factory()->for($this->organization)->create([
        'email' => 'patient@abc-clinic.test',
    ]);

    $this->service->sendMagicLink('patient@abc-clinic.test');

    Notification::assertSentTo($patient, \App\Notifications\PatientMagicLinkNotification::class);
});

it('throws exception when email does not exist', function () {
    expect(fn () => $this->service->sendMagicLink('nonexistent@example.com'))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('verifies valid magic link token and returns patient', function () {
    $patient = Patient::factory()->for($this->organization)->create([
        'email' => 'patient@abc-clinic.test',
    ]);

    $token = $this->service->generateMagicLinkToken($patient);
    Cache::put("patient_magic_link:{$token}", $patient->id, now()->addMinutes(30));

    $verifiedPatient = $this->service->verifyMagicLink($token);

    expect($verifiedPatient)->toBeInstanceOf(Patient::class)
        ->and($verifiedPatient->id)->toBe($patient->id);
});

it('returns null for invalid token', function () {
    $result = $this->service->verifyMagicLink('invalid-token');

    expect($result)->toBeNull();
});

it('returns null for expired token', function () {
    $patient = Patient::factory()->for($this->organization)->create([
        'email' => 'patient@abc-clinic.test',
    ]);

    $token = $this->service->generateMagicLinkToken($patient);
    Cache::put("patient_magic_link:{$token}", $patient->id, now()->subMinute());

    $result = $this->service->verifyMagicLink($token);

    expect($result)->toBeNull();
});

it('removes token after verification (single-use)', function () {
    $patient = Patient::factory()->for($this->organization)->create([
        'email' => 'patient@abc-clinic.test',
    ]);

    $token = $this->service->generateMagicLinkToken($patient);
    Cache::put("patient_magic_link:{$token}", $patient->id, now()->addMinutes(30));

    $this->service->verifyMagicLink($token);

    $cached = Cache::get("patient_magic_link:{$token}");
    expect($cached)->toBeNull();
});

it('cannot reuse token after verification', function () {
    $patient = Patient::factory()->for($this->organization)->create([
        'email' => 'patient@abc-clinic.test',
    ]);

    $token = $this->service->generateMagicLinkToken($patient);
    Cache::put("patient_magic_link:{$token}", $patient->id, now()->addMinutes(30));

    $this->service->verifyMagicLink($token);
    $secondAttempt = $this->service->verifyMagicLink($token);

    expect($secondAttempt)->toBeNull();
});
