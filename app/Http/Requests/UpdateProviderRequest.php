<?php

namespace App\Http\Requests;

use App\Enums\SpeedtestServer;
use App\Models\Provider;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class UpdateProviderRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Provider $provider */
        $provider = $this->route('provider');

        $needsUrl = $provider->slug instanceof SpeedtestServer
            && $provider->slug->requiresServerUrl()
            && $this->boolean('is_enabled');

        return [
            'is_enabled'       => ['required', 'boolean'],
            'alert_on_failure' => ['required', 'boolean'],
            'extra_flags'      => ['nullable', 'string', 'max:255'],
            'server_url'       => [
                $needsUrl ? 'required' : 'nullable',
                'url',
                'max:500',
            ],
            'server_id'        => [
                'nullable',
                'string',
                'regex:/^\d+$/',
                'max:20',
            ],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'server_url.required' => 'A server URL is required to enable LibreSpeed.',
            'server_id.regex'     => 'The Ookla server ID must be numeric.',
        ];
    }
}
