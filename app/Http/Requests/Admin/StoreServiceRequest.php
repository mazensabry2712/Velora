<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'name_ar'     => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'duration'    => 'required|integer|min:5|max:480',
            'price'       => 'nullable|numeric|min:0',
            'is_active'   => 'nullable|boolean',
        ];
    }
}
