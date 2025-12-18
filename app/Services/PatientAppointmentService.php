<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Events\AppointmentCancelled;
use App\Models\Appointment;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PatientAppointmentService
{
    private const CANCELLATION_HOURS_REQUIRED = 24;

    public function getPatientAppointments(Patient $patient, array $filters = []): Collection
    {
        $query = Appointment::query()
            ->where('patient_id', $patient->id)
            ->with(['user', 'examRoom'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc');

        if (isset($filters['status'])) {
            $query->byStatus(AppointmentStatus::from($filters['status']));
        }

        if (isset($filters['upcoming'])) {
            $query->upcoming();
        }

        return $query->get();
    }

    public function canCancelAppointment(Patient $patient, Appointment $appointment): bool
    {
        // Patient must own the appointment
        if ($appointment->patient_id !== $patient->id) {
            return false;
        }

        // Appointment must be cancellable
        if (! $appointment->status->isCancellable()) {
            return false;
        }

        // Check time restriction (must be at least 24 hours before)
        $appointmentDateTime = Carbon::parse($appointment->appointment_date->toDateString().' '.$appointment->appointment_time);
        $hoursUntilAppointment = now()->diffInHours($appointmentDateTime, false);

        return $hoursUntilAppointment >= self::CANCELLATION_HOURS_REQUIRED;
    }

    public function cancelAppointment(Patient $patient, Appointment $appointment, ?string $reason = null): Appointment
    {
        if (! $this->canCancelAppointment($patient, $appointment)) {
            throw new \RuntimeException('This appointment cannot be cancelled. It must be at least 24 hours before the appointment time.');
        }

        return DB::transaction(function () use ($appointment, $reason) {
            $appointment->update([
                'status' => AppointmentStatus::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason ?? 'Cancelled by patient',
            ]);

            event(new AppointmentCancelled($appointment->fresh()));

            return $appointment->fresh();
        });
    }
}
