<?php

namespace App\Services;

use App\Enums\AppointmentStatus;

class AppointmentStatusColorMapper
{
    /**
     * Get the color for an appointment status.
     */
    public function getColor(AppointmentStatus $status): string
    {
        return match ($status) {
            AppointmentStatus::Scheduled => '#3b82f6', // blue
            AppointmentStatus::InProgress => '#f97316', // orange
            AppointmentStatus::Completed => '#10b981', // green
            AppointmentStatus::Cancelled => '#ef4444', // red
            AppointmentStatus::NoShow => '#6b7280', // gray
        };
    }

    /**
     * Get the background color for an appointment status.
     */
    public function getBackgroundColor(AppointmentStatus $status): string
    {
        return match ($status) {
            AppointmentStatus::Scheduled => '#dbeafe', // light blue
            AppointmentStatus::InProgress => '#fed7aa', // light orange
            AppointmentStatus::Completed => '#d1fae5', // light green
            AppointmentStatus::Cancelled => '#fee2e2', // light red
            AppointmentStatus::NoShow => '#f3f4f6', // light gray
        };
    }

    /**
     * Get the text color for an appointment status.
     */
    public function getTextColor(AppointmentStatus $status): string
    {
        return match ($status) {
            AppointmentStatus::Scheduled => '#1e40af', // dark blue
            AppointmentStatus::InProgress => '#c2410c', // dark orange
            AppointmentStatus::Completed => '#065f46', // dark green
            AppointmentStatus::Cancelled => '#991b1b', // dark red
            AppointmentStatus::NoShow => '#374151', // dark gray
        };
    }
}
