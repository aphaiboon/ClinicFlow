<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ExamRoom;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamRoomService
{
    public function __construct(
        private AuditService $auditService
    ) {}

    public function createRoom(array $data): ExamRoom
    {
        return DB::transaction(function () use ($data) {
            if (! isset($data['organization_id']) && Auth::check() && Auth::user()->current_organization_id) {
                $data['organization_id'] = Auth::user()->current_organization_id;
            }

            $room = ExamRoom::create($data);

            $this->auditService->logCreate('ExamRoom', $room->id, $data);

            return $room;
        });
    }

    public function updateRoom(ExamRoom $room, array $data): ExamRoom
    {
        return DB::transaction(function () use ($room, $data) {
            $before = $room->getAttributes();
            $room->update($data);
            $after = $room->fresh()->getAttributes();

            $this->auditService->logUpdate('ExamRoom', $room->id, $before, $after);

            return $room->fresh();
        });
    }

    public function activateRoom(ExamRoom $room): ExamRoom
    {
        return $this->updateRoom($room, ['is_active' => true]);
    }

    public function deactivateRoom(ExamRoom $room): ExamRoom
    {
        return $this->updateRoom($room, ['is_active' => false]);
    }

    public function getAvailableRooms(Carbon $date, Carbon $time, int $duration): Collection
    {
        $dateString = $date->toDateString();
        $requestStartMinutes = $time->hour * 60 + $time->minute;
        $requestEndMinutes = $requestStartMinutes + $duration;

        $conflictingRoomIds = Appointment::where('appointment_date', $dateString)
            ->whereNotNull('exam_room_id')
            ->whereIn('status', [AppointmentStatus::Scheduled, AppointmentStatus::InProgress])
            ->get()
            ->filter(function ($appointment) use ($requestStartMinutes, $requestEndMinutes) {
                $timeParts = explode(':', $appointment->appointment_time);
                $apptStartMinutes = ((int) $timeParts[0]) * 60 + ((int) ($timeParts[1] ?? 0));
                $apptEndMinutes = $apptStartMinutes + $appointment->duration_minutes;

                return $requestStartMinutes < $apptEndMinutes && $requestEndMinutes > $apptStartMinutes;
            })
            ->pluck('exam_room_id')
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        if (empty($conflictingRoomIds)) {
            return ExamRoom::active()->get();
        }

        return ExamRoom::active()
            ->whereNotIn('id', $conflictingRoomIds)
            ->get();
    }
}
