<?php

namespace App\Http\Requests;

use App\Enums\MailDriver;
use App\Models\MailProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMailProviderRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var MailProvider $provider */
        $provider = $this->route('mailProvider');
        $driver = $provider->driver;

        return [
            'label'        => ['sometimes', 'string', 'max:100'],
            'from_address' => ['sometimes', 'email'],
            'from_name'    => ['sometimes', 'string', 'max:100'],
            'is_active'    => ['boolean'],
            'config'       => ['sometimes', 'array'],
            ...$this->driverConfigRules($driver),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function driverConfigRules(MailDriver $driver): array
    {
        return match ($driver) {
            MailDriver::Smtp => [
                'config.host'       => ['sometimes', 'string'],
                'config.port'       => ['sometimes', 'integer', 'min:1', 'max:65535'],
                'config.encryption' => ['sometimes', Rule::in(['tls', 'ssl', 'none'])],
                'config.username'   => ['sometimes', 'string'],
                'config.password'   => ['sometimes', 'string'],
            ],
            MailDriver::Resend => [
                'config.api_key' => ['sometimes', 'string'],
            ],
            MailDriver::Mailgun => [
                'config.api_key'  => ['sometimes', 'string'],
                'config.domain'   => ['sometimes', 'string'],
                'config.endpoint' => ['nullable', 'string'],
            ],
            MailDriver::Postmark => [
                'config.token' => ['sometimes', 'string'],
            ],
            MailDriver::Ses => [
                'config.key'    => ['sometimes', 'string'],
                'config.secret' => ['sometimes', 'string'],
                'config.region' => ['sometimes', 'string'],
            ],
            MailDriver::Sendmail => [
                'config.path' => ['nullable', 'string'],
            ],
        };
    }
}
