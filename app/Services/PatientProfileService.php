<?php

namespace App\Services;

use App\Events\PatientUpdated;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PatientProfileService
{
    /**
     * Get list of fields that patients can edit.
     *
     * @return array<string>
     */
    public function getEditableFields(): array
    {
        return [
            'phone',
            'email',
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'postal_code',
            'country',
        ];
    }

    /**
     * Get list of immutable fields that patients cannot edit.
     *
     * @return array<string>
     */
    public function getImmutableFields(): array
    {
        return [
            'medical_record_number',
            'date_of_birth',
            'first_name',
            'last_name',
            'gender',
            'organization_id',
        ];
    }

    /**
     * Update patient profile with validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePatientProfile(Patient $patient, array $data): Patient
    {
        // Remove immutable fields
        $immutableFields = $this->getImmutableFields();
        foreach ($immutableFields as $field) {
            unset($data[$field]);
        }

        // Only allow editable fields
        $editableFields = $this->getEditableFields();
        $data = array_intersect_key($data, array_flip($editableFields));

        // Validate the data
        $this->validateProfileUpdate($data, $patient);

        return DB::transaction(function () use ($patient, $data) {
            $patient->update($data);

            event(new PatientUpdated($patient));

            return $patient->fresh();
        });
    }

    /**
     * Validate profile update data.
     *
     * @param  array<string, mixed>  $data
     */
    public function validateProfileUpdate(array $data, Patient $patient): void
    {
        $rules = [
            'phone' => ['sometimes', 'required', 'string', 'regex:/^[\d\s\-\+\(\)]{7,20}$/', 'max:20'],
            'email' => ['sometimes', 'required', 'email', 'max:255', 'unique:patients,email,'.$patient->id],
            'address_line_1' => ['sometimes', 'required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'state' => ['sometimes', 'required', 'string', 'max:50'],
            'postal_code' => ['sometimes', 'required', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:2'],
        ];

        // Only validate fields that are present in the data
        $rules = array_intersect_key($rules, $data);

        if (empty($rules)) {
            return; // No fields to validate
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }
}
