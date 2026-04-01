<?php

namespace App\Http\Requests;

use App\Enums\MailDriver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMailProviderRequest extends FormRequest
{
    public function rules(): array
    {
        $driver = MailDriver::tryFrom($this->input('driver'));

        return [
            'driver'       => ['required', Rule::enum(MailDriver::class)],
            'label'        => ['required', 'string', 'max:100'],
            'from_address' => ['required', 'email'],
            'from_name'    => ['required', 'string', 'max:100'],
            'is_active'    => ['boolean'],
            'config'       => ['required', 'array'],
            ...$this->driverConfigRules($driver),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function driverConfigRules(?MailDriver $driver): array
    {
        if (! $driver) {
            return [];
        }

        return match ($driver) {
            MailDriver::Smtp => [
                'config.host'       => ['required', 'string'],
                'config.port'       => ['required', 'integer', 'min:1', 'max:65535'],
                'config.encryption' => ['required', Rule::in(['tls', 'ssl', 'none'])],
                'config.username'   => ['required', 'string'],
                'config.password'   => ['required', 'string'],
            ],
            MailDriver::Resend => [
                'config.api_key' => ['required', 'string'],
            ],
            MailDriver::Mailgun => [
                'config.api_key'  => ['required', 'string'],
                'config.domain'   => ['required', 'string'],
                'config.endpoint' => ['nullable', 'string'],
            ],
            MailDriver::Postmark => [
                'config.token' => ['required', 'string'],
            ],
            MailDriver::Ses => [
                'config.key'    => ['required', 'string'],
                'config.secret' => ['required', 'string'],
                'config.region' => ['required', 'string'],
            ],
            MailDriver::Sendmail => [
                'config.path' => ['nullable', 'string'],
            ],
        };
    }
}
