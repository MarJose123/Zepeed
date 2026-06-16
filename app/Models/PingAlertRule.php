<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property string               $id
 * @property string               $name
 * @property string               $ping_target_id
 * @property string               $condition_operator
 * @property bool                 $is_active
 * @property int                  $cooldown_minutes
 * @property CarbonImmutable|null $last_triggered_at
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property-read PingTarget      $target
 * @property-read HasMany<PingAlertCondition, self> $conditions
 * @property-read HasMany<PingAlertAction, self>    $actions
 */
class PingAlertRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'ping_target_id',
        'condition_operator',
        'is_active',
        'cooldown_minutes',
        'last_triggered_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'is_active'          => 'boolean',
            'cooldown_minutes'   => 'integer',
            'last_triggered_at'  => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<PingTarget, $this> */
    public function target(): BelongsTo
    {
        return $this->belongsTo(PingTarget::class, 'ping_target_id');
    }

    /** @return HasMany<PingAlertCondition, $this> */
    public function conditions(): HasMany
    {
        return $this->hasMany(PingAlertCondition::class, 'ping_alert_rule_id')
            ->orderBy('sort_order');
    }

    /** @return HasMany<PingAlertAction, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(PingAlertAction::class, 'ping_alert_rule_id')
            ->orderBy('sort_order');
    }

    public function isInCooldown(): bool
    {
        if (! $this->last_triggered_at || $this->cooldown_minutes === 0) {
            return false;
        }

        return $this->last_triggered_at->addMinutes($this->cooldown_minutes)->isFuture();
    }
}
