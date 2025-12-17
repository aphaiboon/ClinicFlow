<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        try {
            $appointment = $this->route('appointment');
        } catch (\LogicException $e) {
            return false;
        }

        return $appointment && $this->user()->can('cancel', $appointment);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
