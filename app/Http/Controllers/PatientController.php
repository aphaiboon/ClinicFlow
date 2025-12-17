<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function __construct(
        protected PatientService $patientService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Patient::class);

        $query = Patient::query()->with('appointments');

        if ($request->has('search')) {
            $query->searchByName($request->get('search'));
        }

        $patients = $query->paginate(15)->withQueryString();

        return Inertia::render('Patients/Index', [
            'patients' => $patients,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Patient::class);

        return Inertia::render('Patients/Create');
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $patient = $this->patientService->createPatient($request->validated());

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Patient created successfully.');
    }

    public function show(Patient $patient): Response
    {
        $this->authorize('view', $patient);

        $patient->load('appointments.user', 'appointments.examRoom');

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
        ]);
    }

    public function edit(Patient $patient): Response
    {
        $this->authorize('update', $patient);

        return Inertia::render('Patients/Edit', [
            'patient' => $patient,
        ]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $this->patientService->updatePatient($patient, $request->validated());

        return redirect()->route('patients.show', $patient)
            ->with('success', 'Patient updated successfully.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $this->authorize('delete', $patient);

        $patient->delete();

        return redirect()->route('patients.index')
            ->with('success', 'Patient deleted successfully.');
    }
}
