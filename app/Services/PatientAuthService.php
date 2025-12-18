<?php

namespace App\Services;

use App\Models\Patient;
use App\Notifications\PatientMagicLinkNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PatientAuthService
{
    private const TOKEN_EXPIRATION_MINUTES = 30;

    private const CACHE_PREFIX = 'patient_magic_link:';

    public function sendMagicLink(string $email): void
    {
        $patient = Patient::where('email', $email)->first();

        if (! $patient) {
            throw ValidationException::withMessages([
                'email' => ['We could not find a patient account with that email address.'],
            ]);
        }

        $token = $this->generateMagicLinkToken($patient);

        Cache::put(
            self::CACHE_PREFIX.$token,
            $patient->id,
            now()->addMinutes(self::TOKEN_EXPIRATION_MINUTES)
        );

        $patient->notify(new PatientMagicLinkNotification($token));
    }

    public function verifyMagicLink(string $token): ?Patient
    {
        $patientId = Cache::get(self::CACHE_PREFIX.$token);

        if (! $patientId) {
            return null;
        }

        $patient = Patient::find($patientId);

        if (! $patient) {
            Cache::forget(self::CACHE_PREFIX.$token);

            return null;
        }

        // Remove token (single-use)
        Cache::forget(self::CACHE_PREFIX.$token);

        return $patient;
    }

    public function generateMagicLinkToken(Patient $patient): string
    {
        return Str::random(64);
    }
}
