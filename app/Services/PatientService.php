<?php

namespace App\Services;

use App\Events\PatientCreated;
use App\Events\PatientUpdated;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PatientService
{
    public function createPatient(array $data): Patient
    {
        return DB::transaction(function () use ($data) {
            $data['medical_record_number'] = $this->generateMedicalRecordNumber();

            $patient = Patient::create($data);

            event(new PatientCreated($patient));

            return $patient;
        });
    }

    public function updatePatient(Patient $patient, array $data): Patient
    {
        return DB::transaction(function () use ($patient, $data) {
            $patient->update($data);

            event(new PatientUpdated($patient));

            return $patient->fresh();
        });
    }

    public function findPatient(int $id): ?Patient
    {
        return Patient::find($id);
    }

    public function searchPatients(string $query): Collection
    {
        return Patient::searchByName($query)->get();
    }

    private function generateMedicalRecordNumber(): string
    {
        do {
            $mrn = 'MRN-'.str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Patient::where('medical_record_number', $mrn)->exists());

        return $mrn;
    }
}
