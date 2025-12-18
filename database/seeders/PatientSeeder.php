<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('name', 'ABC Clinic')->first();

        if (! $organization) {
            return;
        }

        // Create 10-15 patients with emails for ABC Clinic
        $patientCount = 12;

        Patient::factory()
            ->count($patientCount)
            ->create([
                'organization_id' => $organization->id,
            ])
            ->each(function ($patient) {
                // Ensure all patients have email addresses for authentication
                $email = $patient->email ?: strtolower($patient->first_name).'.'.strtolower($patient->last_name).'@abc-clinic.test';

                // Make email unique if it already exists
                $baseEmail = $email;
                $counter = 1;
                while (Patient::where('email', $email)->where('id', '!=', $patient->id)->exists()) {
                    $email = str_replace('@abc-clinic.test', $counter.'@abc-clinic.test', $baseEmail);
                    $counter++;
                }

                $patient->update(['email' => $email]);
            });
    }
}
