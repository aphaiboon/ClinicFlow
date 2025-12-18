<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Services\PatientProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PatientProfileController extends Controller
{
    public function __construct(
        protected PatientProfileService $profileService
    ) {}

    public function show(): Response
    {
        $patient = Auth::guard('patient')->user();

        return Inertia::render('Patient/Profile/Show', [
            'patient' => $patient,
            'editableFields' => $this->profileService->getEditableFields(),
        ]);
    }

    public function edit(): Response
    {
        $patient = Auth::guard('patient')->user();

        return Inertia::render('Patient/Profile/Edit', [
            'patient' => $patient,
            'editableFields' => $this->profileService->getEditableFields(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $patient = Auth::guard('patient')->user();

        try {
            $this->profileService->updatePatientProfile($patient, $request->all());

            return redirect()->route('patient.profile.show')
                ->with('success', 'Profile updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }
}
