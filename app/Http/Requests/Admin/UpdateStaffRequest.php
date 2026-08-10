<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $schedule = $this->input('schedule', []);

        if (!is_array($schedule)) {
            return;
        }

        foreach ($schedule as $key => $row) {
            if (!is_array($row)) {
                continue;
            }

            if (isset($row['start_time'])) {
                $schedule[$key]['start_time'] = $this->convertTime(
                    $row['start_time']
                );
            }

            if (isset($row['end_time'])) {
                $schedule[$key]['end_time'] = $this->convertTime(
                    $row['end_time']
                );
            }
        }

        $this->merge([
            'schedule' => $schedule,
        ]);
    }

    private function convertTime($value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $value = trim((string) $value);

        /*
         * Already H:i
         * Example: 09:00
         */
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
            return $value;
        }

        /*
         * H:i:s
         * Example: 09:00:00
         */
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $value)) {
            return substr($value, 0, 5);
        }

        /*
         * 12-hour format
         * Example:
         * 09:00 AM
         * 05:00 PM
         */
        $timestamp = strtotime($value);

        if ($timestamp !== false) {
            return date('H:i', $timestamp);
        }

        return $value;
    }

    public function rules(): array
    {
        $staffId = $this->route('id') ?? $this->route('staff');

        return [
            'name' => 'required|string|max:255',

            'email' => 'required|email|unique:users,email,' . $staffId,

            'phone' => 'nullable|string|max:20',

            'specialization' => 'nullable|string|max:255',

            'services' => 'nullable|array',

            'services.*' => 'integer|exists:services,id',

            'schedule' => 'nullable|array',

            'schedule.*.day_of_week' => [
                'required',
                'integer',
                'min:0',
                'max:6',
            ],

            'schedule.*.start_time' => [
                'required',
                'date_format:H:i',
            ],

            'schedule.*.end_time' => [
                'required',
                'date_format:H:i',
            ],

            'schedule.*.is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $schedule = $this->input('schedule', []);

            if (!is_array($schedule)) {
                return;
            }

            foreach ($schedule as $index => $row) {
                if (!is_array($row)) {
                    continue;
                }

                if (
                    !empty($row['start_time']) &&
                    !empty($row['end_time'])
                ) {
                    if ($row['end_time'] <= $row['start_time']) {
                        $validator->errors()->add(
                            "schedule.$index.end_time",
                            'The end time must be after the start time.'
                        );
                    }
                }
            }
        });
    }
}
