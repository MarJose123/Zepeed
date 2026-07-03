<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SpeedResultIndexRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider'   => ['nullable', 'string', 'exists:providers,slug'],
            'per_page'   => ['nullable', 'integer', 'in:10,25,50,100'],
            'sort'       => ['nullable', 'string', 'in:measured_at,download_mbps,upload_mbps,ping_ms,jitter_ms'],
            'direction'  => ['nullable', 'string', 'in:asc,desc'],
            'date'       => ['nullable', 'date_format:Y-m-d', 'prohibits:date_from,date_to'],
            'date_from'  => ['nullable', 'date_format:Y-m-d', 'prohibits:date'],
            'date_to'    => ['nullable', 'date_format:Y-m-d', 'prohibits:date', 'after_or_equal:date_from'],
        ];
    }
}
