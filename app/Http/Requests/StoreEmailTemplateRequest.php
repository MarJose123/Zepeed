<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailTemplateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:300'],
            'body'    => ['required', 'string'],
        ];
    }
}
