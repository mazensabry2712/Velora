<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required|date_format:H:i',
            'staff_id' => 'nullable|integer|exists:staff,id',
            'service_id' => 'nullable|integer|exists:services,id',
            'service_type' => 'nullable|string|max:255',
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
