<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderMailProvidersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ordered_ids'   => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['required', 'uuid', 'exists:mail_providers,id'],
        ];
    }
}
