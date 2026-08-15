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
            // Expose the config for the edit UI, but never raw credentials —
            // secret values are replaced with a mask sentinel.
            'config'          => self::buildEditableConfig($provider),
            'config_summary'  => self::buildConfigSummary($provider),
            'created_at'      => $provider->created_at->toIso8601String(),
        ];
    }

    /**
     * Build the editable config for the UI: non-secret values as stored,
     * secret values replaced with {@see MailProvider::SECRET_MASK} so
     * credentials never leave the server in plaintext. The update endpoint
     * treats the mask (or an empty value) as "keep the stored value".
     *
     * @return array<string, mixed>
     */
    private static function buildEditableConfig(MailProvider $provider): array
    {
        $config = $provider->config;

        foreach (MailProvider::SECRET_CONFIG_KEYS as $key) {
            if (isset($config[$key]) && $config[$key] !== '') {
                $config[$key] = MailProvider::SECRET_MASK;
            }
        }

        return $config;
    }

    /**
     * Build a safe non-sensitive summary of the config for display.
     *
     * @return string
     */
    private static function buildConfigSummary(MailProvider $provider): string
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
