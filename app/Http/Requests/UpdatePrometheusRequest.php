<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrometheusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'is_enabled'     => ['required', 'boolean'],
            'allowed_ips'    => ['nullable', 'array'],
            'allowed_ips.*'  => ['string', 'max:50'],
            'cache_ttl'      => ['required', 'integer', 'min:10', 'max:3600'],
            'include_speed'  => ['required', 'boolean'],
            'include_ping'   => ['required', 'boolean'],
            'include_system' => ['required', 'boolean'],
            'providers'      => ['required', 'array', 'min:1'],
            'providers.*'    => ['string', 'in:ookla,librespeed,netflix,cloudflare'],
        ];
    }
}
