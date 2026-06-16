<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PingResultIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'target'   => ['nullable', 'uuid', 'exists:ping_targets,id'],
            'status'   => ['nullable', Rule::in(['success', 'partial', 'failed'])],
            'range'    => ['nullable', Rule::in(['24h', '7d', '30d'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
        ];
    }
}
