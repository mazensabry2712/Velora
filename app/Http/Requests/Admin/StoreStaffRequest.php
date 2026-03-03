<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'phone'          => 'nullable|string|max:20',
            'specialization' => 'required|string|max:255',
            'services'       => 'nullable|array',
            'services.*'     => 'integer|exists:services,id',
            'schedule'       => 'nullable|array',
            'schedule.*.day_of_week' => 'required|integer|min:0|max:6',
            'schedule.*.start_time'  => 'required|date_format:H:i',
            'schedule.*.end_time'    => 'required|date_format:H:i|after:schedule.*.start_time',
            'schedule.*.is_active'   => 'nullable|boolean',
        ];
    }
}
