<?php

namespace App\Http\Requests\Settings\Appearance;

use App\Enums\AppearanceEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppearanceUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::enum(AppearanceEnum::class)],
        ];
    }
}
