<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebhookRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'            => ['sometimes', 'string', 'max:100'],
            'url'             => ['sometimes', 'url', 'max:500'],
            'method'          => ['sometimes', Rule::in(['POST', 'GET', 'PUT', 'PATCH'])],
            'secret'          => ['nullable', 'string', 'max:500'],
            'headers'         => ['nullable', 'array'],
            'headers.*.key'   => ['required_with:headers', 'string', 'max:100'],
            'headers.*.value' => ['required_with:headers', 'string', 'max:500'],
            'timeout'         => ['sometimes', 'integer', 'min:1', 'max:120'],
            'retry_attempts'  => ['sometimes', 'integer', 'min:0', 'max:10'],
            'verify_ssl'      => ['boolean'],
            'is_active'       => ['boolean'],
        ];
    }
}
