<?php

namespace App\Models;

use App\Enums\MaintenanceWindowType;
use App\Enums\SpeedtestServer;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property string                $id
 * @property string                $label
 * @property MaintenanceWindowType $type
 * @property bool                  $is_active
 * @property array<int, string>    $providers
 * @property CarbonImmutable|null  $starts_at
 * @property CarbonImmutable|null  $ends_at
 * @property string|null           $cron_expression
 * @property int|null              $duration_minutes
 * @property string|null           $notes
 * @property CarbonImmutable       $created_at
 * @property CarbonImmutable       $updated_at
 *
 * @method static Builder<MaintenanceWindow> active()
 * @method static Builder<MaintenanceWindow> forProvider(SpeedtestServer $server)
 * @method static Builder<MaintenanceWindow> ofType(MaintenanceWindowType $type)
 */
class MaintenanceWindow extends Model
{
    use HasUuids;

    protected $fillable = [
        'label',
        'type',
        'is_active',
        'providers',
        'starts_at',
        'ends_at',
        'cron_expression',
        'duration_minutes',
        'notes',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'type'      => MaintenanceWindowType::class,
            'is_active' => 'boolean',
            'providers' => 'array',
            'starts_at' => 'immutable_datetime',
            'ends_at'   => 'immutable_datetime',
        ];
    }

    /** @param Builder<MaintenanceWindow> $query */
    protected function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<MaintenanceWindow> $query */
    protected function scopeForProvider(Builder $query, SpeedtestServer $server): void
    {
        $query->where(function (Builder $q) use ($server) {
            $q->whereJsonContains('providers', 'all')
                ->orWhereJsonContains('providers', $server->value);
        });
    }

    /** @param Builder<MaintenanceWindow> $query */
    protected function scopeOfType(Builder $query, MaintenanceWindowType $type): void
    {
        $query->where('type', $type->value);
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($this->type) {
            MaintenanceWindowType::Indefinite => true,
            MaintenanceWindowType::OneTime    => $this->isWithinOneTimeWindow(),
            MaintenanceWindowType::Recurring  => $this->isWithinRecurringWindow(),
        };
    }

    private function isWithinOneTimeWindow(): bool
    {
        if (! $this->starts_at || ! $this->ends_at) {
            return false;
        }

        return CarbonImmutable::now()->between($this->starts_at, $this->ends_at);
    }

    private function isWithinRecurringWindow(): bool
    {
        if (! $this->cron_expression || ! $this->duration_minutes) {
            return false;
        }

        $now = CarbonImmutable::now();
        $cron = new CronExpression($this->cron_expression);

        $lastRun = CarbonImmutable::instance(
            $cron->getPreviousRunDate($now->toDateTime(), allowCurrentDate: true)
        );

        $windowEnd = $lastRun->addMinutes($this->duration_minutes);

        return $now->lessThanOrEqualTo($windowEnd);
    }

    public function coversProvider(SpeedtestServer $server): bool
    {
        $providers = $this->providers;

        return in_array('all', $providers, true)
            || in_array($server->value, $providers, true);
    }

    public function coversAllProviders(): bool
    {
        return in_array('all', $this->providers, true);
    }

    public function overlapsWithExisting(): bool
    {
        if ($this->type !== MaintenanceWindowType::OneTime) {
            return false;
        }

        return self::query()
            ->active()
            ->ofType(MaintenanceWindowType::OneTime)
            ->where('id', '!=', $this->id ?? 0)
            ->where(function (Builder $query) {
                $query->where('starts_at', '<', $this->ends_at)
                    ->where('ends_at', '>', $this->starts_at);
            })
            ->where(function (Builder $query) {
                $query->whereJsonContains('providers', 'all');

                foreach ($this->providers as $slug) {
                    $query->orWhereJsonContains('providers', $slug);
                }
            })
            ->exists();
    }

    public static function createGlobalPause(string $notes = ''): self
    {
        return self::query()->create([
            'label'     => 'Global pause',
            'type'      => MaintenanceWindowType::Indefinite->value,
            'is_active' => true,
            'providers' => ['all'],
            'notes'     => $notes,
        ]);
    }

    public static function toggleGlobalPause(bool $active): self
    {
        $window = self::query()
            ->ofType(MaintenanceWindowType::Indefinite)
            ->whereJsonContains('providers', 'all')
            ->firstOrCreate(
                [
                    'type'      => MaintenanceWindowType::Indefinite->value,
                    'providers' => json_encode(['all']),
                ],
                [
                    'label'     => 'Global pause',
                    'is_active' => $active,
                    'providers' => ['all'],
                ]
            );

        $window->update(['is_active' => $active]);

        return $window;
    }
}
