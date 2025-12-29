<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\OrganizationRole;
use App\Http\Requests\AssignRoomRequest;
use App\Http\Requests\CancelAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Services\AppointmentService;
use App\Services\ExamRoomAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService,
        protected ExamRoomAvailabilityService $roomAvailabilityService
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

    public function calendar(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);

        $user = $request->user();
        $organization = $user->currentOrganization;

        if (! $organization) {
            return response()->json(['events' => []]);
        }

        // Determine date range
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->get('start_date'))
            : now()->startOfWeek();
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now()->endOfWeek();

        // Build query with eager loading
        $query = Appointment::query()
            ->with(['patient', 'user', 'examRoom'])
            ->where('organization_id', $organization->id)
            ->whereDate('appointment_date', '>=', $startDate->toDateString())
            ->whereDate('appointment_date', '<=', $endDate->toDateString())
            ->whereNotIn('status', [AppointmentStatus::Cancelled]);

        // Role-based filtering
        if ($user->isSuperAdmin()) {
            // Super admin sees all appointments
        } else {
            $orgRole = $user->getOrganizationRole($organization);

            if ($orgRole === OrganizationRole::Clinician) {
                // Clinicians see only their scheduled appointments
                $query->where('user_id', $user->id);
            } elseif (in_array($orgRole, [OrganizationRole::Admin, OrganizationRole::Owner, OrganizationRole::Receptionist])) {
                // Admins, Owners, and Receptionists see all organization appointments
            }
        }

        // Filter by exam room if provided
        $examRoomId = $request->query('exam_room_id');
        if ($examRoomId !== null && $examRoomId !== '') {
            $query->where('exam_room_id', (int) $examRoomId);
        }

        $appointments = $query->get();

        // Format appointments for FullCalendar
        $events = $appointments->map(function (Appointment $appointment) {
            $startDateTime = Carbon::parse($appointment->appointment_date)
                ->setTimeFromTimeString($appointment->appointment_time);
            $endDateTime = $startDateTime->copy()->addMinutes($appointment->duration_minutes);

            $patientName = $appointment->patient
                ? "{$appointment->patient->first_name} {$appointment->patient->last_name}"
                : 'Unknown Patient';

            $clinicianName = $appointment->user?->name ?? 'Unknown Clinician';

            // Determine event color based on status
            $colors = [
                AppointmentStatus::Scheduled->value => '#3b82f6', // blue
                AppointmentStatus::InProgress->value => '#f97316', // orange
                AppointmentStatus::Completed->value => '#22c55e', // green
                AppointmentStatus::NoShow->value => '#ef4444', // red
            ];

            $statusColor = $colors[$appointment->status->value] ?? '#6b7280';

            return [
                'id' => "appointment-{$appointment->id}",
                'title' => $patientName,
                'start' => $startDateTime->toIso8601String(),
                'end' => $endDateTime->toIso8601String(),
                'backgroundColor' => $statusColor,
                'borderColor' => $statusColor,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'appointmentId' => $appointment->id,
                    'patientId' => $appointment->patient_id,
                    'patientName' => $patientName,
                    'clinicianId' => $appointment->user_id,
                    'clinicianName' => $clinicianName,
                    'examRoomId' => $appointment->exam_room_id,
                    'examRoomName' => $appointment->examRoom?->name,
                    'status' => $appointment->status->value,
                    'appointmentType' => $appointment->appointment_type->value,
                    'durationMinutes' => $appointment->duration_minutes,
                    'notes' => $appointment->notes,
                ],
            ];
        });

        return response()->json(['events' => $events]);
    }

    public function availableRooms(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);

        $user = $request->user();
        $organization = $user->currentOrganization;

        if (! $organization) {
            return response()->json(['rooms' => []]);
        }

        // Determine date range
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->get('start_date'))
            : now()->startOfDay();
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now()->endOfDay();

        // Get optional room filter
        $roomId = $request->filled('room_id') ? (int) $request->get('room_id') : null;

        // Get room availability
        $availability = $this->roomAvailabilityService->getAvailabilityForDateRange(
            $startDate,
            $endDate,
            $roomId,
            $organization->id
        );

        return response()->json(['rooms' => $availability->values()]);
    }
}
