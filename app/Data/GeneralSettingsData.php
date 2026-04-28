<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

final class GeneralSettingsData extends Data
{
    public function __construct(
        #[Url]
        public readonly string $app_url,
        public readonly string $app_env,
        public readonly string $timezone,

        public readonly bool $maintenance_enabled,
        public readonly ?string $bypass_secret,
        #[Min(0)]
        public readonly int $retry_after_value,
        public readonly string $retry_after_unit,
        public readonly ?string $maintenance_redirect,

        public readonly bool $result_auto_purge,
        #[Min(30)]
        public readonly int $result_retention_days,
        public readonly bool $exempt_failed,

        #[Min(30)]
        public readonly int $webhook_retention_days,
        public readonly bool $webhook_extended_retention,

        public readonly string $prune_schedule,
    ) {}

    public static function rules(): array
    {
        return [
            'maintenance_enabled'        => ['required', 'boolean'],
            'result_auto_purge'          => ['required', 'boolean'],
            'exempt_failed'              => ['required', 'boolean'],
            'webhook_extended_retention' => ['required', 'boolean'],

            'app_env'              => ['required', 'string', 'in:production,local,staging'],
            'timezone'             => ['required', 'string', 'timezone:all'],
            'retry_after_unit'     => ['required', 'string', 'in:seconds,minutes'],
            'maintenance_redirect' => ['nullable', 'url', 'max:255'],
            'bypass_secret'        => ['nullable', 'string', 'max:64'],
            'prune_schedule'       => ['required', 'string', 'in:daily_02,daily_04,weekly'],
        ];
    }
}
