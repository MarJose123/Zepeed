<?php

namespace App\Models;

use App\Enums\SpeedtestServer;
use App\Services\Speedtest\Contracts\SpeedtestServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property int                       $id
 * @property SpeedtestServer           $slug
 * @property string                    $name
 * @property bool                      $is_enabled
 * @property bool                      $alert_on_failure
 * @property string|null               $server_url
 * @property string|null               $server_id
 * @property string|null               $extra_flags
 * @property array<string, mixed>|null $meta
 * @property string|null               $last_run_status
 * @property CarbonImmutable|null      $last_run_at
 * @property CarbonImmutable           $created_at
 * @property CarbonImmutable           $updated_at
 * @property-read string               $label
 * @property-read string               $website_link
 * @property-read string               $status_badge
 * @property-read bool                 $is_healthy
 * @property-read bool                 $is_runnable
 *
 * @method static Builder<Provider> enabled()
 * @method static Builder<Provider> forServer(SpeedtestServer $server)
 */
class Provider extends Model
{
    use HasUuids;

    #[Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'slug',
        'name',
        'is_enabled',
        'alert_on_failure',
        'server_url',
        'server_id',
        'extra_flags',
        'meta',
        'last_run_at',
        'last_run_status',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'slug'             => SpeedtestServer::class,
            'is_enabled'       => 'boolean',
            'alert_on_failure' => 'boolean',
            'meta'             => 'array',
            'last_run_at'      => 'immutable_datetime',
            'server_url'       => 'encrypted',
            'server_id'        => 'encrypted',
            'extra_flags'      => 'encrypted',
        ];
    }

    // Scopes

    /** @param Builder<Provider> $query */
    protected function scopeEnabled(Builder $query): void
    {
        $query->where('is_enabled', true);
    }

    /** @param Builder<Provider> $query */
    protected function scopeForServer(Builder $query, SpeedtestServer $server): void
    {
        $query->where('slug', $server->value);
    }

    // Accessors

    protected function label(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $slug = $this->slug;
                if (! $slug instanceof SpeedtestServer) {
                    return '';
                }

                return $slug->label();
            },
        );
    }

    protected function websiteLink(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $slug = $this->slug;
                if (! $slug instanceof SpeedtestServer) {
                    return '';
                }

                return $slug->websiteLink();
            },
        );
    }

    protected function statusBadge(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->last_run_status) {
                'success' => 'success',
                'failed'  => 'danger',
                'skipped' => 'warning',
                default   => 'neutral',
            },
        );
    }

    protected function isHealthy(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->last_run_status === 'success',
        );
    }

    protected function isRunnable(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $slug = $this->slug;
                if (! $slug instanceof SpeedtestServer) {
                    return false;
                }

                return $this->is_enabled
                    && ! ($slug->requiresServerUrl() && empty($this->server_url));
            },
        );
    }

    // Service resolution

    public function service(): SpeedtestServiceInterface
    {
        return resolve(SpeedtestServiceInterface::class, ['provider' => $this]);
    }

    // CLI flag resolution

    public function resolvedFlags(): array
    {
        $slug = $this->slug;

        $defaults = $slug instanceof SpeedtestServer
            ? $slug->defaultFlags()
            : [];

        $extra = $this->extra_flags
            ? explode(' ', trim($this->extra_flags))
            : [];

        /** @var array<int, string> $merged */
        $merged = array_filter(array_merge($defaults, $extra));

        return array_values($merged);
    }

    // State transitions

    public function markSuccessful(): void
    {
        $this->update([
            'last_run_at'     => CarbonImmutable::now(),
            'last_run_status' => 'success',
        ]);
    }

    public function markFailed(): void
    {
        $this->update([
            'last_run_at'     => CarbonImmutable::now(),
            'last_run_status' => 'failed',
        ]);
    }

    public function markSkipped(): void
    {
        $this->update([
            'last_run_at'     => CarbonImmutable::now(),
            'last_run_status' => 'skipped',
        ]);
    }

    // Relations

    /**
     * @return HasMany<SpeedResult, $this>
     */
    public function speedResults(): HasMany
    {
        return $this->hasMany(SpeedResult::class, 'provider_slug', 'slug');
    }
}
