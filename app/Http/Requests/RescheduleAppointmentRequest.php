<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        try {
            $appointment = $this->route('appointment');
        } catch (\LogicException $e) {
            return false;
        }

        return $appointment && $this->user()->can('update', $appointment);
    }

    public function rules(): array
    {
        return [
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['sometimes', 'integer', 'min:15'],
            'force_reschedule' => ['sometimes', 'boolean'],
        ];
    }
}
