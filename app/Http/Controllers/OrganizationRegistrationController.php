<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRegistrationRequest;
use App\Services\OrganizationRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationRegistrationController extends Controller
{
    public function __construct(
        private readonly OrganizationRegistrationService $registrationService
    ) {}

    public function create(): Response
    {
        return Inertia::render('auth/organization-register');
    }

    public function store(StoreOrganizationRegistrationRequest $request): RedirectResponse
    {
        $organizationData = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'address_line_1' => $request->input('address_line_1'),
            'address_line_2' => $request->input('address_line_2'),
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'postal_code' => $request->input('postal_code'),
            'country' => $request->input('country'),
            'tax_id' => $request->input('tax_id'),
            'npi_number' => $request->input('npi_number'),
            'practice_type' => $request->input('practice_type'),
            'license_number' => $request->input('license_number'),
        ];

        $userData = [
            'name' => $request->input('user_name'),
            'email' => $request->input('user_email'),
            'password' => $request->input('password'),
        ];

        $organization = $this->registrationService->register($organizationData, $userData);

        $user = $organization->users()->wherePivot('role', 'owner')->first();
        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
