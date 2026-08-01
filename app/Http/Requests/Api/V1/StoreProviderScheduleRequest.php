<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\SpeedtestServer;
use App\Models\ProviderSchedule;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProviderScheduleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider_slug'   => ['required', Rule::enum(SpeedtestServer::class)],
            'label'           => ['required', 'string', 'max:100'],
            'cron_expression' => [
                'nullable',
                'string',
                static function (string $attribute, mixed $value, Closure $fail) {
                    if ($value && ! ProviderSchedule::isValidCron($value)) {
                        $fail('The cron expression is invalid.');
                    }
                },
            ],
            'is_enabled' => ['required', 'boolean'],
        ];
    }
}
