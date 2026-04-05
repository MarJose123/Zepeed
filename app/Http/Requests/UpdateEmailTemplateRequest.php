<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailTemplateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'    => ['sometimes', 'string', 'max:100'],
            'subject' => ['sometimes', 'string', 'max:300'],
            'body'    => ['sometimes', 'string'],
        ];
    }
}
