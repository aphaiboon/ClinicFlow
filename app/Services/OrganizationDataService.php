<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

class OrganizationDataService
{
    /**
     * Get clinicians (Clinicians, Admins, and Owners) for an organization.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getClinicians(?Organization $organization): array
    {
        if (! $organization) {
            return [];
        }

        return $organization->users()
            ->wherePivotIn('role', [
                OrganizationRole::Clinician->value,
                OrganizationRole::Admin->value,
                OrganizationRole::Owner->value,
            ])
            ->orderBy('name')
            ->get()
            ->values()
            ->toArray();
    }

    /**
     * Get patients for an organization ordered by last name.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPatients(?Organization $organization): array
    {
        if (! $organization) {
            return [];
        }

        return $organization->patients()
            ->orderBy('last_name')
            ->get()
            ->values()
            ->toArray();
    }

    /**
     * Get active exam rooms for an organization ordered by room number.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getExamRooms(?Organization $organization): array
    {
        if (! $organization) {
            return [];
        }

        return $organization->examRooms()
            ->where('is_active', true)
            ->orderBy('room_number')
            ->get()
            ->values()
            ->toArray();
    }

    /**
     * Get operating hours for an organization with defaults.
     *
     * @return array{startTime: string, endTime: string}
     */
    public function getOperatingHours(?Organization $organization): array
    {
        return [
            'startTime' => $organization?->operating_hours_start ?? '08:00:00',
            'endTime' => $organization?->operating_hours_end ?? '18:00:00',
        ];
    }

    /**
     * Get time slot interval preference, falling back to organization default or 15.
     */
    public function getTimeSlotInterval(?Organization $organization, ?User $user = null): int
    {
        if ($user && $user->calendar_time_slot_interval !== null) {
            return $user->calendar_time_slot_interval;
        }

        if ($organization && $organization->default_time_slot_interval !== null) {
            return $organization->default_time_slot_interval;
        }

        return 15; // Default fallback
    }
}
