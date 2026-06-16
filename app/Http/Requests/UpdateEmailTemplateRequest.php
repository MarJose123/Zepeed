<?php

namespace App\Http\Requests;

use App\Enums\EmailTemplateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailTemplateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'string', 'max:100'],
            'subject'       => ['sometimes', 'string', 'max:300'],
            'body'          => ['sometimes', 'string'],
            'template_type' => ['sometimes', Rule::enum(EmailTemplateType::class)],
        ];
    }
}
