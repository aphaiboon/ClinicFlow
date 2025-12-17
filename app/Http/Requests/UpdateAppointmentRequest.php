<?php

namespace App\Http\Requests;

use App\Enums\AppointmentType;
use App\Models\ExamRoom;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
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
            'patient_id' => ['sometimes', 'required', 'exists:'.Patient::class.',id'],
            'user_id' => ['sometimes', 'required', 'exists:'.User::class.',id'],
            'exam_room_id' => ['nullable', 'exists:'.ExamRoom::class.',id'],
            'appointment_date' => ['sometimes', 'required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['sometimes', 'required', 'date_format:H:i'],
            'duration_minutes' => ['sometimes', 'required', 'integer', 'min:15', 'max:240'],
            'appointment_type' => ['sometimes', 'required', Rule::enum(AppointmentType::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
