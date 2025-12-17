<?php

namespace App\Http\Requests;

use App\Models\ExamRoom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        try {
            $room = $this->route('exam_room') ?? $this->route('examRoom');
        } catch (\LogicException $e) {
            return false;
        }

        return $room && $this->user()->can('update', $room);
    }

    public function rules(): array
    {
        $roomId = $this->route('exam_room')?->id ?? $this->route('examRoom')?->id;

        return [
            'room_number' => ['sometimes', 'required', 'string', 'max:255', Rule::unique(ExamRoom::class, 'room_number')->ignore($roomId)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'floor' => ['nullable', 'integer', 'min:1'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => ['string', 'max:255'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1', 'max:10'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
