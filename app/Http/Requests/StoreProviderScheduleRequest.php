<?php

namespace App\Http\Requests;

use App\Models\ProviderSchedule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreProviderScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'provider_slug'   => ['required', 'string'],
            'label'           => ['required', 'string', 'max:100'],
            'cron_expression' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($value && ! ProviderSchedule::isValidCron($value)) {
                        $fail('The cron expression is invalid.');
                    }
                },
            ],
            'is_enabled' => ['required', 'boolean'],
        ];
    }
}
