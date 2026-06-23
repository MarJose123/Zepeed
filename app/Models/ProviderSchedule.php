<?php

namespace App\Models;

use App\Enums\SpeedtestServer;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Database\Factories\ProviderScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use Lacodix\LaravelModelFilter\Filters\BooleanFilter;
use Lacodix\LaravelModelFilter\Traits\HasFilters;
use Lacodix\LaravelModelFilter\Traits\IsSortable;
use Override;

/**
 * @property string               $id
 * @property SpeedtestServer      $provider_slug
 * @property string               $label
 * @property string|null          $cron_expression
 * @property bool                 $is_enabled
 * @property CarbonImmutable|null $last_scheduled_at
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 *
 * @method static Builder<ProviderSchedule> enabled()
 * @method static Builder<ProviderSchedule> forProvider(SpeedtestServer $server)
 */
#[UseFactory(ProviderScheduleFactory::class)]
class ProviderSchedule extends Model
{
    use HasFactory, HasFilters, HasUuids, IsSortable;

    protected array $sortable = [
        'created_at' => 'desc',
    ];

    protected $fillable = [
        'provider_slug',
        'label',
        'cron_expression',
        'is_enabled',
        'last_scheduled_at',
    ];

    /**
     * Define filters for ProviderSchedule model.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            BooleanFilter::forModel(static::class)
                ->make('is_enabled')
                ->setQueryName('enabled')
                ->setTitle('Enabled'),
        ];
    }

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

    public static function allForProvider(SpeedtestServer $server): Collection
    {
        return self::query()
            ->forProvider($server)->oldest()
            ->get();
    }

    public static function enabledForProvider(SpeedtestServer $server): Collection
    {
        return self::query()
            ->enabled()
            ->forProvider($server)->oldest()
            ->get();
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
