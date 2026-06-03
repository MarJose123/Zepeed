<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SpeedResultIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['nullable', 'string', 'exists:speed_results,provider_slug'],
            'month'    => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ];
    }
}
