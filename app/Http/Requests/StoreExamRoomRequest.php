<?php

namespace App\Http\Requests;

use App\Models\ExamRoom;
use Illuminate\Foundation\Http\FormRequest;

class StoreExamRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ExamRoom::class);
    }

    public function rules(): array
    {
        return [
            'room_number' => ['required', 'string', 'max:255', 'unique:'.ExamRoom::class.',room_number'],
            'name' => ['required', 'string', 'max:255'],
            'floor' => ['nullable', 'integer', 'min:1'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => ['string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
