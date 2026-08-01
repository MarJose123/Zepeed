<?php

namespace App\Http\Requests\Account\Settings\ApiToken;

use App\Enums\TokenAbility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

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
            'name'         => ['required', 'string', 'max:255'],
            'expires_at'   => ['nullable', 'date', 'after:now'],
            'abilities'    => ['required', 'array', 'min:1'],
            'abilities.*'  => ['string', 'distinct', Rule::in(TokenAbility::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'abilities.required' => 'Select at least one token ability.',
            'abilities.min'      => 'Select at least one token ability.',
            'abilities.*.in'     => 'One or more selected abilities are invalid.',
        ];
    }
}
