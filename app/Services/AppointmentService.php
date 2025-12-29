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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function __construct(
        private ExamRoomService $examRoomService,
        private AppointmentConflictService $conflictService
    ) {}

    public function scheduleAppointment(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $date = Carbon::parse($data['appointment_date']);
            $time = TimeParser::parse($data['appointment_time']);

            if ($this->conflictService->hasClinicianConflict(
                $data['user_id'],
                $date,
                $time,
                $data['duration_minutes']
            )) {
                throw new \RuntimeException('Clinician is not available at the requested time.');
            }

            if (isset($data['exam_room_id']) && $data['exam_room_id']) {
                $room = ExamRoom::findOrFail($data['exam_room_id']);
                if ($this->conflictService->hasRoomConflict(
                    $room->id,
                    $date,
                    $time,
                    $data['duration_minutes']
                )) {
                    throw new \RuntimeException('Room is not available at the requested time.');
                }
            }

            $data['status'] = AppointmentStatus::Scheduled;

            if (! isset($data['organization_id']) && Auth::check() && Auth::user()->current_organization_id) {
                $data['organization_id'] = Auth::user()->current_organization_id;
            }

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
            if ($status === AppointmentStatus::Cancelled) {
                if ($appointment->status === AppointmentStatus::Completed) {
                    throw new \RuntimeException('Cannot cancel a completed appointment.');
                }
                if ($appointment->status === AppointmentStatus::Cancelled) {
                    throw new \RuntimeException('Appointment is already cancelled.');
                }
            }

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
            $time = TimeParser::parse($appointment->appointment_time);

            if ($this->conflictService->hasRoomConflict(
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
            $time = TimeParser::parse($data['appointment_time'] ?? $appointment->appointment_time);
            $duration = $data['duration_minutes'] ?? $appointment->duration_minutes;
            $userId = $data['user_id'] ?? $appointment->user_id;

            if (($data['appointment_date'] ?? null) || ($data['appointment_time'] ?? null) || ($data['user_id'] ?? null) || ($data['duration_minutes'] ?? null)) {
                if ($this->conflictService->hasClinicianConflict($userId, $date, $time, $duration, $appointment->id)) {
                    throw new \RuntimeException('Clinician is not available at the requested time.');
                }
            }

            if (isset($data['exam_room_id']) && $data['exam_room_id']) {
                $room = ExamRoom::findOrFail($data['exam_room_id']);
                if ($this->conflictService->hasRoomConflict($room->id, $date, $time, $duration, $appointment->id)) {
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

    public function checkRescheduleConflicts(
        Appointment $appointment,
        Carbon $newDate,
        Carbon $newTime,
        int $duration
    ): array {
        $conflicts = [];

        // Check clinician conflicts
        $clinicianConflicts = $this->conflictService->findClinicianConflicts(
            $appointment->user_id,
            $newDate,
            $newTime,
            $duration,
            $appointment->id
        )->load('patient');

        if ($clinicianConflicts->isNotEmpty()) {
            $conflicts[] = [
                'type' => 'clinician',
                'message' => 'Clinician has a conflicting appointment at this time.',
                'conflictingAppointments' => $clinicianConflicts->map(function ($apt) {
                    return [
                        'id' => $apt->id,
                        'patientName' => $apt->patient ? "{$apt->patient->first_name} {$apt->patient->last_name}" : 'Unknown',
                        'time' => "{$apt->appointment_time} - ".Carbon::parse($apt->appointment_time)->addMinutes($apt->duration_minutes)->format('H:i'),
                    ];
                })->toArray(),
            ];
        }

        // Check exam room conflicts (if room is assigned)
        if ($appointment->exam_room_id) {
            $roomConflicts = $this->conflictService->findRoomConflicts(
                $appointment->exam_room_id,
                $newDate,
                $newTime,
                $duration,
                $appointment->id
            )->load('patient');

            if ($roomConflicts->isNotEmpty()) {
                $conflicts[] = [
                    'type' => 'room',
                    'message' => 'Exam room is occupied at this time.',
                    'conflictingAppointments' => $roomConflicts->map(function ($apt) {
                        return [
                            'id' => $apt->id,
                            'patientName' => $apt->patient ? "{$apt->patient->first_name} {$apt->patient->last_name}" : 'Unknown',
                            'time' => "{$apt->appointment_time} - ".Carbon::parse($apt->appointment_time)->addMinutes($apt->duration_minutes)->format('H:i'),
                        ];
                    })->toArray(),
                ];
            }
        }

        return $conflicts;
    }

    public function rescheduleAppointment(
        Appointment $appointment,
        Carbon $newDate,
        Carbon $newTime,
        int $duration
    ): Appointment {
        return DB::transaction(function () use ($appointment, $newDate, $newTime, $duration) {
            $appointment->update([
                'appointment_date' => $newDate->toDateString(),
                'appointment_time' => $newTime->format('H:i:s'),
                'duration_minutes' => $duration,
            ]);

            event(new AppointmentUpdated($appointment));

            return $appointment->fresh();
        });
    }
}
