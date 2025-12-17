<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Events\AppointmentCancelled;
use App\Events\AppointmentScheduled;
use App\Events\AppointmentUpdated;
use App\Events\RoomAssigned;
use App\Models\Appointment;
use App\Models\ExamRoom;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function __construct(
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

            event(new AppointmentScheduled($appointment));

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

            if ($status === AppointmentStatus::Cancelled) {
                event(new AppointmentCancelled($appointment->fresh()));
            } else {
                event(new AppointmentUpdated($appointment->fresh()));
            }

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

            $appointment->update(['exam_room_id' => $room->id]);

            event(new RoomAssigned($appointment->fresh()));

            return $appointment->fresh();
        });
    }

    public function cancelAppointment(Appointment $appointment, string $reason): Appointment
    {
        return $this->updateAppointmentStatus($appointment, AppointmentStatus::Cancelled, $reason);
    }

    public function updateAppointment(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            $date = Carbon::parse($data['appointment_date'] ?? $appointment->appointment_date->toDateString());
            $timeParts = explode(':', $data['appointment_time'] ?? $appointment->appointment_time);
            $time = Carbon::createFromTime((int) $timeParts[0], (int) ($timeParts[1] ?? 0), (int) ($timeParts[2] ?? 0));
            $duration = $data['duration_minutes'] ?? $appointment->duration_minutes;
            $userId = $data['user_id'] ?? $appointment->user_id;

            if (($data['appointment_date'] ?? null) || ($data['appointment_time'] ?? null) || ($data['user_id'] ?? null) || ($data['duration_minutes'] ?? null)) {
                if (! $this->checkClinicianAvailability($userId, $date, $time, $duration, $appointment->id)) {
                    throw new \RuntimeException('Clinician is not available at the requested time.');
                }
            }

            if (isset($data['exam_room_id']) && $data['exam_room_id']) {
                $room = ExamRoom::findOrFail($data['exam_room_id']);
                if (! $this->checkRoomAvailability($room->id, $date, $time, $duration, $appointment->id)) {
                    throw new \RuntimeException('Room is not available at the requested time.');
                }
            }

            $appointment->update($data);

            event(new AppointmentUpdated($appointment->fresh()));

            return $appointment->fresh();
        });
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
        $requestStartMinutes = $time->hour * 60 + $time->minute;
        $requestEndMinutes = $requestStartMinutes + $duration;

        $appointments = Appointment::whereDate('appointment_date', $date->toDateString())
            ->where('exam_room_id', $roomId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->when($excludeAppointmentId, fn ($q) => $q->where('id', '!=', $excludeAppointmentId))
            ->get();

        return $appointments->filter(function ($appointment) use ($requestStartMinutes, $requestEndMinutes) {
            $timeStr = $appointment->appointment_time;
            $timeParts = explode(':', $timeStr);
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
        $requestStartMinutes = $time->hour * 60 + $time->minute;
        $requestEndMinutes = $requestStartMinutes + $duration;

        $appointments = Appointment::whereDate('appointment_date', $date->toDateString())
            ->where('user_id', $userId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->when($excludeAppointmentId, fn ($q) => $q->where('id', '!=', $excludeAppointmentId))
            ->get();

        return $appointments->filter(function ($appointment) use ($requestStartMinutes, $requestEndMinutes) {
            $timeStr = $appointment->appointment_time;
            $timeParts = explode(':', $timeStr);
            $apptStartMinutes = ((int) $timeParts[0]) * 60 + ((int) ($timeParts[1] ?? 0));
            $apptEndMinutes = $apptStartMinutes + $appointment->duration_minutes;

            return $requestStartMinutes < $apptEndMinutes && $requestEndMinutes > $apptStartMinutes;
        });
    }
}
