<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppriseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'       => ['sometimes', 'string', 'max:100'],
            'url'        => ['sometimes', 'url', 'max:500'],
            'tags'       => ['nullable', 'array'],
            'tags.*'     => ['string', 'max:100'],
            'username'   => ['nullable', 'string', 'max:255'],
            'password'   => ['nullable', 'string', 'max:500'],
            'timeout'    => ['sometimes', 'integer', 'min:1', 'max:120'],
            'verify_ssl' => ['boolean'],
            'is_active'  => ['boolean'],
        ];
    }

    /**
     * Validated data with password handling applied: an absent or blank
     * password keeps the existing credential, a non-empty password replaces
     * it. This prevents an empty form field from silently wiping the stored
     * Basic Auth password on every edit.
     *
     * @return array<string, mixed>
     */
    public function passwordAwareValidated(): array
    {
        $data = $this->validated();

        if (! array_key_exists('password', $data) || blank($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
