<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ExportFormat;
use App\Enums\ExportModule;
use App\Models\Provider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class StoreExportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'module'    => ['required', Rule::enum(ExportModule::class)],
            'format'    => ['required', Rule::enum(ExportFormat::class)],
            'provider'  => [
                'nullable',
                'string',
                Rule::in(Provider::query()->pluck('slug')->toArray()),
                'prohibited_if:module,ping_result',
            ],
            'target' => [
                'nullable',
                'uuid',
                Rule::exists('ping_targets', 'id'),
                'prohibited_unless:module,ping_result',
            ],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to'   => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    /** @return array<string, string> */
    #[Override]
    public function messages(): array
    {
        return [
            'date_from.required'       => 'A start date is required to export.',
            'date_to.required'         => 'An end date is required to export.',
            'provider.prohibited_if'   => 'The provider filter is only valid for speedtest export modules.',
            'target.prohibited_unless' => 'The target filter is only valid for the ping_result module.',
        ];
    }
}
