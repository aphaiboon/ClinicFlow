<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AppointmentQueryBuilder
{
    /**
     * Build a query for appointments index with filters.
     */
    public function buildIndexQuery(Request $request, ?int $organizationId = null): Builder
    {
        $query = Appointment::query()
            ->with(['patient', 'user', 'examRoom']);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $this->applyStatusFilter($query, $request);
        $this->applyDateFilter($query, $request);
        $this->applyClinicianFilter($query, $request);
        $this->applyExamRoomFilter($query, $request);

        return $query->latest('appointment_date');
    }

    /**
     * Get paginated appointments for index page.
     */
    public function getPaginatedAppointments(Request $request, ?int $organizationId = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->buildIndexQuery($request, $organizationId)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Build query for calendar view with role-based filtering.
     */
    public function buildCalendarQuery(
        Request $request,
        int $organizationId,
        ?int $userId = null,
        ?string $userRole = null
    ): Builder {
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->get('start_date'))
            : now()->startOfDay();
        $endDate = $request->has('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now()->endOfDay();

        $query = Appointment::query()
            ->with(['patient', 'user', 'examRoom'])
            ->where('organization_id', $organizationId)
            ->whereDate('appointment_date', '>=', $startDate->toDateString())
            ->whereDate('appointment_date', '<=', $endDate->toDateString())
            ->whereNotIn('status', [AppointmentStatus::Cancelled]);

        // Role-based filtering
        if ($userRole === 'clinician' && $userId) {
            $query->where('user_id', $userId);
        }

        // Filter by exam room if provided
        $examRoomId = $request->query('exam_room_id');
        if ($examRoomId !== null && $examRoomId !== '') {
            $query->where('exam_room_id', (int) $examRoomId);
        }

        return $query;
    }

    /**
     * Apply status filter to query.
     */
    private function applyStatusFilter(Builder $query, Request $request): void
    {
        if (! $request->has('status') || $request->get('status') === 'all') {
            return;
        }

        $query->byStatus(AppointmentStatus::from($request->get('status')));
    }

    /**
     * Apply date filter to query.
     */
    private function applyDateFilter(Builder $query, Request $request): void
    {
        if (! $request->has('date')) {
            return;
        }

        $date = Carbon::parse($request->get('date'));
        $query->byDateRange($date, $date);
    }

    /**
     * Apply clinician filter to query.
     */
    private function applyClinicianFilter(Builder $query, Request $request): void
    {
        if (! $request->has('clinician_id') || $request->get('clinician_id') === 'all') {
            return;
        }

        $query->byClinician($request->get('clinician_id'));
    }

    /**
     * Apply exam room filter to query.
     */
    private function applyExamRoomFilter(Builder $query, Request $request): void
    {
        if (! $request->has('exam_room_id') || $request->get('exam_room_id') === 'all' || $request->get('exam_room_id') === 'none') {
            return;
        }

        $query->where('exam_room_id', $request->get('exam_room_id'));
    }
}
