<?php

namespace App\Http\Requests;

use App\Enums\ExportFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class ExportPingResultRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'format'    => ['required', Rule::enum(ExportFormat::class)],
            'target'    => ['nullable', 'uuid', 'exists:ping_targets,id'],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to'   => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    /** @return array<string, string> */
    #[Override]
    public function messages(): array
    {
        return [
            'date_from.required' => 'A start date is required to export.',
            'date_to.required'   => 'An end date is required to export.',
        ];
    }
}
