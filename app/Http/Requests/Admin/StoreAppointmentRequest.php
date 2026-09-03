<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
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
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'staff_id' => 'required|integer|exists:staff,id',
            'service_id' => 'nullable|integer|exists:services,id',
            'service_type' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'add_to_queue' => 'nullable|boolean',
            'queue_date' => 'nullable|date|after_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => __('Customer name is required.'),
            'customer_phone.required' => __('Customer phone is required.'),
            'appointment_date.required' => __('Appointment date is required.'),
            'appointment_date.after_or_equal' => __('Appointment date cannot be in the past.'),
            'appointment_time.required' => __('Appointment time is required.'),
            'appointment_time.date_format' => __('Appointment time must use HH:MM format.'),
            'staff_id.required' => __('Staff member is required.'),
            'staff_id.exists' => __('Selected staff member does not exist.'),
        ];
    }
}
