<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PublicBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return tenant() !== null;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{N}\s\-\.]+$/u'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^[\d\+\-\(\)\s]+$/'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'service_id' => [
                'required',
                Rule::exists('services', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->where('is_online_bookable', true)),
            ],
            'staff_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'resource_id' => [
                'nullable',
                Rule::exists('resources', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'timezone' => ['nullable', 'timezone'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
