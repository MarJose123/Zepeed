<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

final class GeneralSettingsData extends Data
{
    public function __construct(
        // ── Application ───────────────────────────────────────────────────
        #[Url]
        public readonly string $app_url,
        public readonly string $timezone,

        // ── Maintenance ───────────────────────────────────────────────────
        public readonly bool $maintenance_enabled,
        public readonly ?string $bypass_secret,
        #[Min(0)]
        public readonly int $retry_after_value,
        public readonly string $retry_after_unit,
        public readonly ?string $maintenance_redirect,

        // ── Pruning ───────────────────────────────────────────────────────
        public readonly bool $result_auto_purge,
        #[Min(30)]
        public readonly int $result_retention_days,
        public readonly string $prune_schedule,
        public readonly ?string $prune_cron,
        #[Min(100)]
        #[Max(10000)]
        public readonly int $batch_size,
        public readonly bool $exempt_failed,

        // ── Webhooks ──────────────────────────────────────────────────────
        #[Min(30)]
        public readonly int $webhook_retention_days,
        public readonly bool $webhook_extended_retention,
    ) {}

    /**
     * Validation rules.
     *
     * The 'boolean' rule runs before spatie casts, so it coerces
     * "true"/"false"/1/0 strings into PHP booleans that the typed
     * constructor properties then receive correctly.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            // Explicit boolean coercion — critical for MariaDB round-trips
            // where HTTP sends "true"/"false" or 1/0 as strings.
            'maintenance_enabled'        => ['required', 'boolean'],
            'result_auto_purge'          => ['required', 'boolean'],
            'exempt_failed'              => ['required', 'boolean'],
            'webhook_extended_retention' => ['required', 'boolean'],

            // Other fields
            'timezone'             => ['required', 'string', 'timezone:all'],
            'retry_after_unit'     => ['required', 'string', 'in:seconds,minutes'],
            'prune_schedule'       => ['required', 'string', 'in:daily_02,daily_04,weekly,custom'],
            'prune_cron'           => ['nullable', 'required_if:prune_schedule,custom', 'string', 'max:100'],
            'maintenance_redirect' => ['nullable', 'url', 'max:255'],
            'bypass_secret'        => ['nullable', 'string', 'max:64'],
        ];
    }
}
