<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignRoomRequest;
use App\Http\Requests\CancelAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Appointment::class);

        $query = Appointment::query()
            ->with(['patient', 'user', 'examRoom']);

        if ($request->has('status')) {
            $query->byStatus(\App\Enums\AppointmentStatus::from($request->get('status')));
        }

        if ($request->has('date')) {
            $date = \Carbon\Carbon::parse($request->get('date'));
            $query->byDateRange($date, $date);
        }

        if ($request->has('clinician_id')) {
            $query->byClinician($request->get('clinician_id'));
        }

        $appointments = $query->latest('appointment_date')->paginate(15)->withQueryString();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'filters' => $request->only(['status', 'date', 'clinician_id']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Appointment::class);

        $organization = auth()->user()->currentOrganization;

        return Inertia::render('Appointments/Create', [
            'patients' => $organization?->patients()->orderBy('last_name')->get() ?? collect(),
            'clinicians' => $organization?->users()
                ->wherePivotIn('role', [\App\Enums\OrganizationRole::Clinician->value, \App\Enums\OrganizationRole::Admin->value, \App\Enums\OrganizationRole::Owner->value])
                ->orderBy('name')
                ->get() ?? collect(),
            'examRooms' => $organization?->examRooms()->where('is_active', true)->orderBy('room_number')->get() ?? collect(),
        ]);
    }

    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        try {
            $appointment = $this->appointmentService->scheduleAppointment($request->validated());

            return redirect()->route('appointments.show', $appointment)
                ->with('success', 'Appointment scheduled successfully.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(Appointment $appointment): Response
    {
        $this->authorize('view', $appointment);

        $appointment->load(['patient', 'user', 'examRoom']);

        return Inertia::render('Appointments/Show', [
            'appointment' => $appointment,
            'examRooms' => \App\Models\ExamRoom::where('is_active', true)->orderBy('room_number')->get(),
        ]);
    }

    public function edit(Appointment $appointment): Response
    {
        $this->authorize('update', $appointment);

        $appointment->load(['patient', 'user', 'examRoom']);

        $organization = auth()->user()->currentOrganization;

        return Inertia::render('Appointments/Edit', [
            'appointment' => $appointment,
            'patients' => $organization?->patients()->orderBy('last_name')->get() ?? collect(),
            'clinicians' => $organization?->users()
                ->wherePivotIn('role', [\App\Enums\OrganizationRole::Clinician->value, \App\Enums\OrganizationRole::Admin->value, \App\Enums\OrganizationRole::Owner->value])
                ->orderBy('name')
                ->get() ?? collect(),
            'examRooms' => $organization?->examRooms()->where('is_active', true)->orderBy('room_number')->get() ?? collect(),
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        try {
            $updateData = array_merge($appointment->only([
                'patient_id', 'user_id', 'exam_room_id', 'appointment_date',
                'appointment_time', 'duration_minutes', 'appointment_type', 'notes',
            ]), $request->validated());

            if (isset($updateData['appointment_date']) && is_string($updateData['appointment_date'])) {
                $updateData['appointment_date'] = \Carbon\Carbon::parse($updateData['appointment_date'])->toDateString();
            } else {
                $updateData['appointment_date'] = $appointment->appointment_date->toDateString();
            }

            $this->appointmentService->updateAppointment($appointment, $updateData);

            return redirect()->route('appointments.show', $appointment)
                ->with('success', 'Appointment updated successfully.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function cancel(CancelAppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $this->appointmentService->cancelAppointment($appointment, $request->validated()['reason']);

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Appointment cancelled successfully.');
    }

    public function assignRoom(AssignRoomRequest $request, Appointment $appointment): RedirectResponse
    {
        try {
            $room = ExamRoom::findOrFail($request->validated()['exam_room_id']);
            $this->appointmentService->assignRoom($appointment, $room);

            return redirect()->route('appointments.show', $appointment)
                ->with('success', 'Room assigned successfully.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
}
