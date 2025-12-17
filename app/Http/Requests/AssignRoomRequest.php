<?php

namespace App\Http\Requests;

use App\Models\ExamRoom;
use Illuminate\Foundation\Http\FormRequest;

class AssignRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        try {
            $appointment = $this->route('appointment');
        } catch (\LogicException $e) {
            return false;
        }

        return $appointment && $this->user()->can('assignRoom', $appointment);
    }

    public function rules(): array
    {
        return [
            'exam_room_id' => ['required', 'exists:'.ExamRoom::class.',id'],
        ];
    }
}
