<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppriseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100'],
            'url'        => ['required', 'url', 'max:500'],
            'tags'       => ['nullable', 'array'],
            'tags.*'     => ['string', 'max:100'],
            'username'   => ['nullable', 'string', 'max:255'],
            'password'   => ['nullable', 'string', 'max:500'],
            'timeout'    => ['integer', 'min:1', 'max:120'],
            'verify_ssl' => ['boolean'],
            'is_active'  => ['boolean'],
        ];
    }
}
