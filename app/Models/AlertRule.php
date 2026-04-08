<?php

namespace App\Models;

use App\Enums\AlertRuleEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property string               $id
 * @property string               $name
 * @property string|null          $provider_slug
 * @property AlertRuleEvent       $event
 * @property string               $condition_operator
 * @property bool                 $is_active
 * @property int                  $cooldown_minutes
 * @property CarbonImmutable|null $last_triggered_at
 * @property-read HasMany<AlertRuleCondition, self> $conditions
 * @property-read HasMany<AlertRuleAction, self>    $actions
 */
class AlertRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'provider_slug',
        'event',
        'condition_operator',
        'is_active',
        'cooldown_minutes',
        'last_triggered_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'event'              => AlertRuleEvent::class,
            'is_active'          => 'boolean',
            'cooldown_minutes'   => 'integer',
            'last_triggered_at'  => 'immutable_datetime',
        ];
    }

    /** @return HasMany<AlertRuleCondition, $this> */
    public function conditions(): HasMany
    {
        return $this->hasMany(AlertRuleCondition::class)->orderBy('sort_order');
    }

    /** @return HasMany<AlertRuleAction, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(AlertRuleAction::class)->orderBy('sort_order');
    }

    /**
     * Whether this rule is in its cooldown window and should not re-fire.
     */
    public function isInCooldown(): bool
    {
        if (! $this->last_triggered_at || $this->cooldown_minutes === 0) {
            return false;
        }

        return $this->last_triggered_at->addMinutes($this->cooldown_minutes)->isFuture();
    }
}
