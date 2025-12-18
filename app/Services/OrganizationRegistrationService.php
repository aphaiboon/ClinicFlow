<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OrganizationRegistrationService
{
    public function __construct(
        private readonly OrganizationService $organizationService
    ) {}

    public function register(array $organizationData, array $userData): Organization
    {
        $this->validate($organizationData, $userData);

        return DB::transaction(function () use ($organizationData, $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
            ]);

            $organization = $this->organizationService->create($organizationData, $user);

            $user->update(['current_organization_id' => $organization->id]);

            return $organization->fresh();
        });
    }

    private function validate(array $organizationData, array $userData): void
    {
        $organizationRules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:2'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'npi_number' => ['nullable', 'string', 'max:255'],
            'practice_type' => ['nullable', 'string', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:255'],
        ];

        $userRules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ];

        $allData = array_merge($organizationData, $userData);
        $allRules = array_merge($organizationRules, $userRules);

        $validator = Validator::make($allData, $allRules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }
}
