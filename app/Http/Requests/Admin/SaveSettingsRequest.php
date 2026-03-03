<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_name'         => 'nullable|string|max:255',
            'business_name_ar'      => 'nullable|string|max:255',
            'phone'                 => 'nullable|string|max:50',
            'email'                 => 'nullable|email|max:255',
            'address'               => 'nullable|string|max:500',
            'whatsapp'              => 'nullable|string|max:50',
            'facebook'              => 'nullable|url|max:255',
            'instagram'             => 'nullable|url|max:255',
            'twitter'               => 'nullable|url|max:255',
            'tiktok'                => 'nullable|url|max:255',
            'snapchat'              => 'nullable|string|max:100',
            'available_languages'   => 'nullable|array',
            'available_languages.*' => 'string|in:en,ar,fr,es,de,it,pt,ru,zh,ja',
            'logo'                  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }
}
