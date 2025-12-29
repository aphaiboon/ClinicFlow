<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AppointmentConflictService
{
    /**
     * Check if clinician has a conflict at the given time.
     */
    public function hasClinicianConflict(
        int $userId,
        Carbon $date,
        Carbon $time,
        int $duration,
        ?int $excludeAppointmentId = null
    ): bool {
        return ! $this->findClinicianConflicts($userId, $date, $time, $duration, $excludeAppointmentId)->isEmpty();
    }

    /**
     * Check if exam room has a conflict at the given time.
     */
    public function hasRoomConflict(
        int $roomId,
        Carbon $date,
        Carbon $time,
        int $duration,
        ?int $excludeAppointmentId = null
    ): bool {
        return ! $this->findRoomConflicts($roomId, $date, $time, $duration, $excludeAppointmentId)->isEmpty();
    }

    /**
     * Find all conflicting appointments for a clinician.
     *
     * @return Collection<int, Appointment>
     */
    public function findClinicianConflicts(
        int $userId,
        Carbon $date,
        Carbon $time,
        int $duration,
        ?int $excludeAppointmentId = null
    ): Collection {
        $requestStart = $date->copy()->setTime($time->hour, $time->minute, 0);
        $requestEnd = $requestStart->copy()->addMinutes($duration);

        $appointments = Appointment::whereDate('appointment_date', $date->toDateString())
            ->where('user_id', $userId)
            ->whereIn('status', [AppointmentStatus::Scheduled->value, AppointmentStatus::InProgress->value])
            ->when($excludeAppointmentId, fn ($q) => $q->where('id', '!=', $excludeAppointmentId))
            ->get();

        return $appointments->filter(function (Appointment $appointment) use ($requestStart, $requestEnd) {
            $appointmentStart = Carbon::parse($appointment->appointment_date)
                ->setTimeFromTimeString($appointment->appointment_time);
            $appointmentEnd = $appointmentStart->copy()->addMinutes($appointment->duration_minutes);

            return $this->appointmentsOverlap($requestStart, $requestEnd, $appointmentStart, $appointmentEnd);
        });
    }

    /**
     * Find all conflicting appointments for an exam room.
     *
     * @return Collection<int, Appointment>
     */
    public function findRoomConflicts(
        int $roomId,
        Carbon $date,
        Carbon $time,
        int $duration,
        ?int $excludeAppointmentId = null
    ): Collection {
        $requestStart = $date->copy()->setTime($time->hour, $time->minute, 0);
        $requestEnd = $requestStart->copy()->addMinutes($duration);

        $appointments = Appointment::whereDate('appointment_date', $date->toDateString())
            ->where('exam_room_id', $roomId)
            ->whereIn('status', [AppointmentStatus::Scheduled->value, AppointmentStatus::InProgress->value])
            ->when($excludeAppointmentId, fn ($q) => $q->where('id', '!=', $excludeAppointmentId))
            ->get();

        return $appointments->filter(function (Appointment $appointment) use ($requestStart, $requestEnd) {
            $appointmentStart = Carbon::parse($appointment->appointment_date)
                ->setTimeFromTimeString($appointment->appointment_time);
            $appointmentEnd = $appointmentStart->copy()->addMinutes($appointment->duration_minutes);

            return $this->appointmentsOverlap($requestStart, $requestEnd, $appointmentStart, $appointmentEnd);
        });
    }

    /**
     * Check if two appointment time ranges overlap.
     *
     * Two appointments overlap if: start1 < end2 AND end1 > start2
     */
    public function appointmentsOverlap(
        Carbon $start1,
        Carbon $end1,
        Carbon $start2,
        Carbon $end2
    ): bool {
        return $start1->lt($end2) && $end1->gt($start2);
    }
}
