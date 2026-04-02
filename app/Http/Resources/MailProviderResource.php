<?php

namespace App\Http\Resources;

use App\Enums\MailDriver;
use App\Models\MailProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class MailProviderResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        $provider = $this->provider();

        return [
            'id'                 => $provider->id,
            'driver'             => $provider->driver->value,
            'driver_label'       => $provider->driver->label(),
            'driver_description' => $provider->driver->description(),
            'label'              => $provider->label,
            'priority'           => $provider->priority,
            'is_active'          => $provider->is_active,
            'from_address'       => $provider->from_address,
            'from_name'          => $provider->from_name,
            'last_used_at'       => $provider->last_used_at?->toIso8601String(),
            'last_failed_at'     => $provider->last_failed_at?->toIso8601String(),
            'failure_count'      => $provider->failure_count,
            'is_primary'         => $provider->priority === 1,
            // Never expose raw config — only expose non-sensitive indicators
            'config_summary'  => $this->buildConfigSummary($provider),
            'created_at'      => $provider->created_at->toIso8601String(),
        ];
    }

    /**
     * Build a safe non-sensitive summary of the config for display.
     *
     * @return string
     */
    private function buildConfigSummary(MailProvider $provider): string
    {
        $config = $provider->config;

        return match ($provider->driver) {
            MailDriver::Smtp     => "{$config['host']}:{$config['port']}",
            MailDriver::Resend   => 'via API key',
            MailDriver::Mailgun  => $config['domain'] ?? 'via API key',
            MailDriver::Postmark => 'via token',
            MailDriver::Ses      => $config['region'] ?? 'via AWS keys',
            MailDriver::Sendmail => $config['path'] ?? '/usr/sbin/sendmail',
        };
    }

    private function provider(): MailProvider
    {
        /** @var MailProvider */
        return $this->resource;
    }
}
