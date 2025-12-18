<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\PatientAppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PatientAppointmentController extends Controller
{
    public function __construct(
        protected PatientAppointmentService $appointmentService
    ) {}

    public function index(Request $request): Response
    {
        $patient = Auth::guard('patient')->user();

        $filters = $request->only(['status', 'upcoming']);

        $appointments = $this->appointmentService->getPatientAppointments($patient, $filters);

        return Inertia::render('Patient/Appointments/Index', [
            'appointments' => $appointments,
            'filters' => $filters,
        ]);
    }

    public function show(Appointment $appointment): Response
    {
        $patient = Auth::guard('patient')->user();

        // Ensure patient owns this appointment
        if ($appointment->patient_id !== $patient->id) {
            abort(403);
        }

        $appointment->load(['user', 'examRoom']);

        return Inertia::render('Patient/Appointments/Show', [
            'appointment' => $appointment,
            'canCancel' => $this->appointmentService->canCancelAppointment($patient, $appointment),
        ]);
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $patient = Auth::guard('patient')->user();

        // Ensure patient owns this appointment
        if ($appointment->patient_id !== $patient->id) {
            abort(403);
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->appointmentService->cancelAppointment($patient, $appointment, $request->input('reason'));

            return redirect()->route('patient.appointments.show', $appointment)
                ->with('success', 'Appointment cancelled successfully.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['cancellation' => $e->getMessage()]);
        }
    }
}
