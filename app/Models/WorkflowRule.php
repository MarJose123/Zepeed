<?php

namespace App\Models;

use App\Enums\WorkflowRuleEvent;
use Carbon\CarbonImmutable;
use Database\Factories\WorkflowRuleFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property string               $id
 * @property string               $name
 * @property string|null          $provider_slug
 * @property string|null          $ping_target_id
 * @property WorkflowRuleEvent    $event
 * @property string               $condition_operator
 * @property bool                 $is_active
 * @property int                  $cooldown_minutes
 * @property CarbonImmutable|null $last_triggered_at
 * @property-read PingTarget|null             $target
 * @property-read HasMany<WorkflowRuleCondition, self> $conditions
 * @property-read HasMany<WorkflowRuleAction, self>    $actions
 */
#[UseFactory(WorkflowRuleFactory::class)]
class WorkflowRule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'provider_slug',
        'ping_target_id',
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
            'event'              => WorkflowRuleEvent::class,
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

    /** @return HasMany<WorkflowRuleCondition, $this> */
    public function conditions(): HasMany
    {
        return $this->hasMany(WorkflowRuleCondition::class)->orderBy('sort_order');
    }

    /** @return HasMany<WorkflowRuleAction, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowRuleAction::class)->orderBy('sort_order');
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
