<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentCalendarFormatter
{
    public function __construct(
        private AppointmentStatusColorMapper $colorMapper
    ) {}

    /**
     * Format an appointment for FullCalendar display.
     *
     * @return array<string, mixed>
     */
    public function format(Appointment $appointment): array
    {
        $startDateTime = Carbon::parse($appointment->appointment_date)
            ->setTimeFromTimeString($appointment->appointment_time);
        $endDateTime = $startDateTime->copy()->addMinutes($appointment->duration_minutes);

        $patientName = $appointment->patient
            ? "{$appointment->patient->first_name} {$appointment->patient->last_name}"
            : 'Unknown Patient';

        $clinicianName = $appointment->user?->name ?? 'Unknown Clinician';

        $statusColor = $this->colorMapper->getColor($appointment->status);

        return [
            'id' => "appointment-{$appointment->id}",
            'title' => $patientName,
            'start' => $startDateTime->toIso8601String(),
            'end' => $endDateTime->toIso8601String(),
            'backgroundColor' => $statusColor,
            'borderColor' => $statusColor,
            'textColor' => '#ffffff',
            'extendedProps' => [
                'appointmentId' => $appointment->id,
                'patientId' => $appointment->patient_id,
                'patientName' => $patientName,
                'clinicianId' => $appointment->user_id,
                'clinicianName' => $clinicianName,
                'examRoomId' => $appointment->exam_room_id,
                'examRoomName' => $appointment->examRoom?->name,
                'status' => $appointment->status->value,
                'appointmentType' => $appointment->appointment_type->value,
                'durationMinutes' => $appointment->duration_minutes,
                'notes' => $appointment->notes,
            ],
        ];
    }

    /**
     * Get color for appointment status.
     */
    private function getStatusColor(AppointmentStatus $status): string
    {
        return match ($status) {
            AppointmentStatus::Scheduled => '#3b82f6', // blue
            AppointmentStatus::InProgress => '#f97316', // orange
            AppointmentStatus::Completed => '#22c55e', // green
            AppointmentStatus::NoShow => '#ef4444', // red
            default => '#6b7280', // gray
        };
    }
}
