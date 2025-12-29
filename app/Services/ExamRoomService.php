<?php

namespace App\Services;

use App\Models\ExamRoom;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamRoomService
{
    public function __construct(
        private AuditService $auditService,
        private AppointmentConflictService $conflictService
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
        $allRooms = ExamRoom::active()->get();

        return $allRooms->filter(function (ExamRoom $room) use ($date, $time, $duration) {
            return ! $this->conflictService->hasRoomConflict($room->id, $date, $time, $duration);
        });
    }
}
