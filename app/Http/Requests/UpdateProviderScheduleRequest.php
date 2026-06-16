<?php

namespace App\Http\Requests;

use App\Models\ProviderSchedule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'label'           => ['sometimes', 'string', 'max:100'],
            'cron_expression' => [
                'nullable',
                'string',
                static function (string $attribute, mixed $value, Closure $fail) {
                    if ($value && ! ProviderSchedule::isValidCron($value)) {
                        $fail('The cron expression is invalid.');
                    }
                },
            ],
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
