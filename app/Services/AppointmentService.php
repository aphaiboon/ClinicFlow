<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ExamRoom;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function __construct(
        private AuditService $auditService,
        private ExamRoomService $examRoomService
    ) {}

    public function scheduleAppointment(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $date = Carbon::parse($data['appointment_date']);
            $timeParts = explode(':', $data['appointment_time']);
            $time = Carbon::createFromTime((int) $timeParts[0], (int) ($timeParts[1] ?? 0), (int) ($timeParts[2] ?? 0));

            if (! $this->checkClinicianAvailability(
                $data['user_id'],
                $date,
                $time,
                $data['duration_minutes']
            )) {
                throw new \RuntimeException('Clinician is not available at the requested time.');
            }

            if (isset($data['exam_room_id']) && $data['exam_room_id']) {
                $room = ExamRoom::findOrFail($data['exam_room_id']);
                if (! $this->checkRoomAvailability(
                    $room->id,
                    $date,
                    $time,
                    $data['duration_minutes']
                )) {
                    throw new \RuntimeException('Room is not available at the requested time.');
                }
            }

            $data['status'] = AppointmentStatus::Scheduled;
            $appointment = Appointment::create($data);

            $this->auditService->logCreate('Appointment', $appointment->id, $data);

            return $appointment;
        });
    }

    public function updateAppointmentStatus(
        Appointment $appointment,
        AppointmentStatus $status,
        ?string $reason = null
    ): Appointment {
        return DB::transaction(function () use ($appointment, $status, $reason) {
            $before = $appointment->getAttributes();

            $updateData = ['status' => $status];
            if ($status === AppointmentStatus::Cancelled) {
                $updateData['cancelled_at'] = now();
                $updateData['cancellation_reason'] = $reason;
            }

            $appointment->update($updateData);
            $after = $appointment->fresh()->getAttributes();

            $this->auditService->logUpdate('Appointment', $appointment->id, $before, $after);

            return $appointment->fresh();
        });
    }

    public function assignRoom(Appointment $appointment, ExamRoom $room): Appointment
    {
        return DB::transaction(function () use ($appointment, $room) {
            if (! $room->is_active) {
                throw new \RuntimeException('Room is not active.');
            }

            $date = Carbon::parse($appointment->appointment_date);
            $timeParts = explode(':', $appointment->appointment_time);
            $time = Carbon::createFromTime((int) $timeParts[0], (int) ($timeParts[1] ?? 0), (int) ($timeParts[2] ?? 0));

            if (! $this->checkRoomAvailability(
                $room->id,
                $date,
                $time,
                $appointment->duration_minutes,
                $appointment->id
            )) {
                throw new \RuntimeException('Room is not available at the appointment time.');
            }

            $before = $appointment->getAttributes();
            $appointment->update(['exam_room_id' => $room->id]);
            $after = $appointment->fresh()->getAttributes();

            $this->auditService->logUpdate('Appointment', $appointment->id, $before, $after);

            return $appointment->fresh();
        });
    }

    public function cancelAppointment(Appointment $appointment, string $reason): Appointment
    {
        return $this->updateAppointmentStatus($appointment, AppointmentStatus::Cancelled, $reason);
    }

    public function checkClinicianAvailability(
        int $userId,
        Carbon $date,
        Carbon $time,
        int $duration,
        ?int $excludeAppointmentId = null
    ): bool {
        $conflicts = $this->findConflictingAppointments($userId, $date, $time, $duration, $excludeAppointmentId);

        return $conflicts->isEmpty();
    }

    public function checkRoomAvailability(
        int $roomId,
        Carbon $date,
        Carbon $time,
        int $duration,
        ?int $excludeAppointmentId = null
    ): bool {
        $conflicts = $this->findConflictingRoomAppointments($roomId, $date, $time, $duration, $excludeAppointmentId);

        return $conflicts->isEmpty();
    }

    private function findConflictingRoomAppointments(
        int $roomId,
        Carbon $date,
        Carbon $time,
        int $duration,
        ?int $excludeAppointmentId = null
    ): Collection {
        $dateString = $date->toDateString();
        $requestStartMinutes = $time->hour * 60 + $time->minute;
        $requestEndMinutes = $requestStartMinutes + $duration;

        return Appointment::where('appointment_date', $dateString)
            ->where('exam_room_id', $roomId)
            ->whereIn('status', [AppointmentStatus::Scheduled, AppointmentStatus::InProgress])
            ->when($excludeAppointmentId, fn ($q) => $q->where('id', '!=', $excludeAppointmentId))
            ->get()
            ->filter(function ($appointment) use ($requestStartMinutes, $requestEndMinutes) {
                $timeParts = explode(':', $appointment->appointment_time);
                $apptStartMinutes = ((int) $timeParts[0]) * 60 + ((int) ($timeParts[1] ?? 0));
                $apptEndMinutes = $apptStartMinutes + $appointment->duration_minutes;

                return $requestStartMinutes < $apptEndMinutes && $requestEndMinutes > $apptStartMinutes;
            });
    }

    public function findConflictingAppointments(
        int $userId,
        Carbon $date,
        Carbon $time,
        int $duration,
        ?int $excludeAppointmentId = null
    ): Collection {
        $dateString = $date->toDateString();
        $requestStartMinutes = $time->hour * 60 + $time->minute;
        $requestEndMinutes = $requestStartMinutes + $duration;

        return Appointment::where('appointment_date', $dateString)
            ->where('user_id', $userId)
            ->whereIn('status', [AppointmentStatus::Scheduled, AppointmentStatus::InProgress])
            ->when($excludeAppointmentId, fn ($q) => $q->where('id', '!=', $excludeAppointmentId))
            ->get()
            ->filter(function ($appointment) use ($requestStartMinutes, $requestEndMinutes) {
                $timeParts = explode(':', $appointment->appointment_time);
                $apptStartMinutes = ((int) $timeParts[0]) * 60 + ((int) ($timeParts[1] ?? 0));
                $apptEndMinutes = $apptStartMinutes + $appointment->duration_minutes;

                return $requestStartMinutes < $apptEndMinutes && $requestEndMinutes > $apptStartMinutes;
            });
    }
}
