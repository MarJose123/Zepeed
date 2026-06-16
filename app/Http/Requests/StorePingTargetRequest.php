<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePingTargetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'label'           => ['required', 'string', 'max:255', 'unique:ping_targets,label'],
            'host'            => ['required', 'string', 'max:255'],
            'packets'         => ['required', 'integer', 'min:1', 'max:50'],
            'timeout_seconds' => ['required', 'integer', 'min:1', 'max:30'],
            'is_enabled'      => ['boolean'],
        ];
    }
}
