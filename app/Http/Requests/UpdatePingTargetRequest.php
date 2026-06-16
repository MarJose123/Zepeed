<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePingTargetRequest extends FormRequest
{
    public function rules(): array
    {
        $targetId = $this->route('pingTarget')?->id;

        return [
            'label'           => ['sometimes', 'string', 'max:255', Rule::unique('ping_targets', 'label')->ignore($targetId)],
            'host'            => ['sometimes', 'string', 'max:255'],
            'packets'         => ['sometimes', 'integer', 'min:1', 'max:50'],
            'timeout_seconds' => ['sometimes', 'integer', 'min:1', 'max:30'],
            'is_enabled'      => ['boolean'],
        ];
    }
}
