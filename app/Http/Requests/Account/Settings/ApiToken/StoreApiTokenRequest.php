<?php

namespace App\Http\Requests\Account\Settings\ApiToken;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreApiTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
