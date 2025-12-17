<?php

namespace App\Http\Requests;

use App\Enums\AppointmentType;
use App\Models\Appointment;
use App\Models\ExamRoom;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Appointment::class);
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:'.Patient::class.',id'],
            'user_id' => ['required', 'exists:'.User::class.',id'],
            'exam_room_id' => ['nullable', 'exists:'.ExamRoom::class.',id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:240'],
            'appointment_type' => ['required', Rule::enum(AppointmentType::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
