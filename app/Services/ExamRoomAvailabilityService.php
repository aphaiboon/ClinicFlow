<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ExamRoom;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ExamRoomAvailabilityService
{
    public function __construct(
        private AppointmentService $appointmentService
    ) {}

    public function getAvailabilityForDateRange(
        Carbon $start,
        Carbon $end,
        ?int $roomId = null,
        ?int $organizationId = null
    ): SupportCollection {
        $query = ExamRoom::where('is_active', true);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        if ($roomId) {
            $query->where('id', $roomId);
        }

        $rooms = $query->get();

        return $rooms->map(function (ExamRoom $room) use ($start, $end) {
            $conflictingAppointments = $this->findConflictingAppointments(
                $room->id,
                $start,
                $end,
                $room->organization_id
            );

            $availability = $conflictingAppointments->isEmpty() ? 'available' : 'busy';

            return [
                'roomId' => $room->id,
                'roomName' => $room->name,
                'roomNumber' => $room->room_number,
                'isActive' => $room->is_active,
                'availability' => $availability,
                'conflictingAppointments' => $conflictingAppointments->map(function (Appointment $appointment) {
                    $appointmentStart = Carbon::parse($appointment->appointment_date)
                        ->setTimeFromTimeString($appointment->appointment_time);
                    $appointmentEnd = $appointmentStart->copy()->addMinutes($appointment->duration_minutes);

                    return [
                        'id' => $appointment->id,
                        'start' => $appointmentStart->toIso8601String(),
                        'end' => $appointmentEnd->toIso8601String(),
                        'patientName' => $appointment->patient
                            ? "{$appointment->patient->first_name} {$appointment->patient->last_name}"
                            : 'Unknown Patient',
                    ];
                })->toArray(),
            ];
        });
    }

    private function findConflictingAppointments(
        int $roomId,
        Carbon $rangeStart,
        Carbon $rangeEnd,
        ?int $organizationId = null
    ): Collection {
        // Get all dates in the range
        $dates = [];
        $currentDate = $rangeStart->copy()->startOfDay();
        while ($currentDate->lte($rangeEnd->endOfDay())) {
            $dates[] = $currentDate->toDateString();
            $currentDate->addDay();
        }

        $query = Appointment::where('exam_room_id', $roomId)
            ->where(function ($q) use ($dates) {
                foreach ($dates as $date) {
                    $q->orWhereDate('appointment_date', $date);
                }
            })
            ->whereIn('status', [AppointmentStatus::Scheduled, AppointmentStatus::InProgress]);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $appointments = $query->with('patient')->get();

        return $appointments->filter(function (Appointment $appointment) use ($rangeStart, $rangeEnd) {
            $appointmentDate = Carbon::parse($appointment->appointment_date);
            $appointmentStart = $appointmentDate->copy()->setTimeFromTimeString($appointment->appointment_time);
            $appointmentEnd = $appointmentStart->copy()->addMinutes($appointment->duration_minutes);

            // Check if appointment overlaps with the time range
            // Overlap occurs if: appointment starts before range ends AND appointment ends after range starts
            return $appointmentStart->lt($rangeEnd) && $appointmentEnd->gt($rangeStart);
        });
    }
}
