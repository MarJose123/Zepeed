<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property int    $id
 * @property string $key
 * @property mixed  $value
 *
 * @method static Builder<static> query()
 */
final class Setting extends Model
{
    use HasUuids;

    /** @var list<string> */
    protected $fillable = ['key', 'value'];

    // ─── Static helpers ───────────────────────────────────────────────────

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    /**
     * Return all general settings with explicit type coercions.
     *
     * The JSON cast returns MariaDB JSON column values as their native PHP
     * types (bool stays bool), but we coerce defensively in case any setting
     * was written before the JSON cast was applied.
     *
     * @return array{
     *     app_url: string,
     *     timezone: string,
     *     maintenance_enabled: bool,
     *     bypass_secret: string,
     *     retry_after_value: int,
     *     retry_after_unit: string,
     *     maintenance_redirect: string,
     *     result_auto_purge: bool,
     *     result_retention_days: int,
     *     prune_schedule: string,
     *     exempt_failed: bool,
     *     webhook_retention_days: int,
     *     webhook_extended_retention: bool,
     *     prune_schedule: string,
     * }
     */
    public static function generalSettings(): array
    {
        return [
            'app_url'                    => (string) self::get('app_url', config('app.url', '')),
            'app_env'                    => (string) self::get('app_env', config('app.env', 'production')),
            'timezone'                   => (string) self::get('timezone', config('app.timezone', 'UTC')),

            'maintenance_enabled'        => (bool) self::get('maintenance_enabled', false),
            'bypass_secret'              => (string) self::get('bypass_secret', ''),
            'retry_after_value'          => (int) self::get('retry_after_value', 60),
            'retry_after_unit'           => (string) self::get('retry_after_unit', 'seconds'),
            'maintenance_redirect'       => (string) self::get('maintenance_redirect', ''),

            'result_auto_purge'          => (bool) self::get('result_auto_purge', false),
            'result_retention_days'      => (int) self::get('result_retention_days', 90),
            'exempt_failed'              => (bool) self::get('exempt_failed', false),

            'prune_frequency'    => (string) self::get('prune_frequency', 'daily'),
            'prune_hour'         => (int) self::get('prune_hour', 2),
            'prune_day_of_week'  => (int) self::get('prune_day_of_week', 0),
            'prune_day_of_month' => (int) self::get('prune_day_of_month', 1),

            'webhook_retention_days'     => (int) self::get('webhook_retention_days', 30),
            'webhook_extended_retention' => (bool) self::get('webhook_extended_retention', true),
        ];
    }

    /**
     * Store value as JSON so booleans survive the MariaDB round-trip as
     * actual booleans (true/false in JSON), not the strings "1"/"0".
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return ['value' => 'json'];
    }
}
