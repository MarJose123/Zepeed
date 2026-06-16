<?php

namespace App\Models;

use App\Enums\PingStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property string               $id
 * @property string               $label
 * @property string               $host
 * @property bool                 $is_enabled
 * @property int                  $packets
 * @property int                  $timeout_seconds
 * @property PingStatus           $status
 * @property float|null           $last_avg_ms
 * @property float|null           $last_loss_percent
 * @property CarbonImmutable|null $last_tested_at
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property-read HasMany<PingResult, self>    $results
 * @property-read HasMany<PingAlertRule, self> $alertRules
 */
class PingTarget extends Model
{
    use HasUuids;

    protected $fillable = [
        'label',
        'host',
        'is_enabled',
        'packets',
        'timeout_seconds',
        'status',
        'last_avg_ms',
        'last_loss_percent',
        'last_tested_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'is_enabled'        => 'boolean',
            'packets'           => 'integer',
            'timeout_seconds'   => 'integer',
            'status'            => PingStatus::class,
            'last_avg_ms'       => 'float',
            'last_loss_percent' => 'float',
            'last_tested_at'    => 'immutable_datetime',
        ];
    }

    /** @return HasMany<PingResult, $this> */
    public function results(): HasMany
    {
        return $this->hasMany(PingResult::class)->latest('measured_at');
    }

    /** @return HasMany<PingAlertRule, $this> */
    public function alertRules(): HasMany
    {
        return $this->hasMany(PingAlertRule::class);
    }

    /** Scope: only enabled targets. */
    protected function scopeEnabled(Builder $query): void
    {
        $query->where('is_enabled', true);
    }

    /**
     * Sync the summary columns on the target after a result is stored.
     */
    public function syncFromResult(PingResult $result): void
    {
        $this->update([
            'status'            => $result->deriveTargetStatus(),
            'last_avg_ms'       => $result->avg_ms,
            'last_loss_percent' => $result->packet_loss_percent,
            'last_tested_at'    => $result->measured_at,
        ]);
    }
}
