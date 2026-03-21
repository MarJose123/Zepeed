<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceWindowType;
use App\Enums\SpeedtestServer;
use App\Models\MaintenanceWindow;
use Closure;
use Cron\CronExpression;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class StoreMaintenanceWindowRequest extends FormRequest
{
    public function rules(): array
    {
        $type = MaintenanceWindowType::tryFrom($this->input('type'));

        return [
            'label' => ['required', 'string', 'max:100'],

            'type' => [
                'required',
                Rule::enum(MaintenanceWindowType::class),
            ],

            'is_active' => ['boolean'],

            'providers'   => ['required', 'array', 'min:1'],
            'providers.*' => [
                Rule::in([
                    'all',
                    ...array_column(SpeedtestServer::cases(), 'value'),
                ]),
            ],

            // One-time fields — required only when type = one_time
            'starts_at' => [
                $type?->requiresDateRange() ? 'required' : 'nullable',
                'date',
                'before:ends_at',
            ],
            'ends_at' => [
                $type?->requiresDateRange() ? 'required' : 'nullable',
                'date',
                'after:starts_at',
            ],

            // Recurring fields — required only when type = recurring
            'cron_expression' => [
                $type?->requiresCronExpression() ? 'required' : 'nullable',
                'string',
                // Validate cron syntax via a closure
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! $value) {
                        return;
                    }

                    try {
                        new CronExpression($value);
                    } catch (InvalidArgumentException) {
                        $fail('The cron expression is invalid.');
                    }
                },
            ],
            'duration_minutes' => [
                $type?->requiresCronExpression() ? 'required' : 'nullable',
                'integer',
                'min:1',
                'max:1440', // max 24 hours
            ],

            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * After base validation passes, check for overlapping windows.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $window = new MaintenanceWindow($this->validated());

            if ($window->overlapsWithExisting()) {
                $validator->errors()->add(
                    'starts_at',
                    'This window overlaps with an existing active maintenance window for the same provider(s).'
                );
            }
        });
    }
}
