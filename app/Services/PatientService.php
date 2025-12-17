<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PatientService
{
    public function __construct(
        private AuditService $auditService
    ) {}

    public function createPatient(array $data): Patient
    {
        return DB::transaction(function () use ($data) {
            $data['medical_record_number'] = $this->generateMedicalRecordNumber();

            $patient = Patient::create($data);

            $this->auditService->logCreate('Patient', $patient->id, $data);

            return $patient;
        });
    }

    public function updatePatient(Patient $patient, array $data): Patient
    {
        return DB::transaction(function () use ($patient, $data) {
            $before = $patient->getAttributes();
            $patient->update($data);
            $after = $patient->fresh()->getAttributes();

            $this->auditService->logUpdate('Patient', $patient->id, $before, $after);

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
