<?php

namespace App\Models;

use App\Enums\SpeedtestServer;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use Override;

/**
 * @property string               $id
 * @property SpeedtestServer      $provider_slug
 * @property string|null          $cron_expression
 * @property bool                 $is_enabled
 * @property CarbonImmutable|null $last_scheduled_at
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 *
 * @method static Builder<ProviderSchedule> enabled()
 * @method static Builder<ProviderSchedule> forProvider(SpeedtestServer $server)
 */
class ProviderSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'provider_slug',
        'cron_expression',
        'is_enabled',
        'last_scheduled_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'provider_slug'     => SpeedtestServer::class,
            'is_enabled'        => 'boolean',
            'last_scheduled_at' => 'immutable_datetime',
        ];
    }

    /** @param Builder<ProviderSchedule> $query */
    protected function scopeEnabled(Builder $query): void
    {
        $query->where('is_enabled', true);
    }

    /** @param Builder<ProviderSchedule> $query */
    protected function scopeForProvider(Builder $query, SpeedtestServer $server): void
    {
        $query->where('provider_slug', $server->value);
    }

    public static function forProvider(SpeedtestServer $server): ?self
    {
        return self::query()
            ->enabled()
            ->forProvider($server)
            ->first();
    }

    public function nextRunAt(): ?CarbonImmutable
    {
        if (! $this->cron_expression) {
            return null;
        }

        return CarbonImmutable::instance(
            new CronExpression($this->cron_expression)->getNextRunDate()
        );
    }

    public static function isValidCron(string $expression): bool
    {
        try {
            new CronExpression($expression);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'slug');
    }
}
