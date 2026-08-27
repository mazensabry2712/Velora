<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class RequestSubscriptionUpgradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && tenant('id') !== null;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:mysql.subscription_plans,id'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
