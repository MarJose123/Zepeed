<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Override;

class OoklaServerSearchRequest extends FormRequest
{
    /**
     * @return array<string, mixed[]>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'q.required' => 'A search query is required.',
            'q.min'      => 'Search query must be at least 2 characters.',
        ];
    }
}
