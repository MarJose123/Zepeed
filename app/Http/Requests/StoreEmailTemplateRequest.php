<?php

namespace App\Http\Requests;

use App\Enums\EmailTemplateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmailTemplateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100'],
            'subject'       => ['required', 'string', 'max:300'],
            'body'          => ['required', 'string'],
            'template_type' => ['required', Rule::enum(EmailTemplateType::class)],
        ];
    }
}
