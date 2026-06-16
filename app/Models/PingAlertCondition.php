<?php

namespace App\Models;

use App\Enums\PingAlertMetric;
use App\Enums\PingAlertOperator;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property string            $id
 * @property string            $ping_alert_rule_id
 * @property PingAlertMetric   $metric
 * @property PingAlertOperator $operator
 * @property string            $value
 * @property int               $lookback_minutes
 * @property int               $sort_order
 */
class PingAlertCondition extends Model
{
    use HasUuids;

    protected $fillable = [
        'ping_alert_rule_id',
        'metric',
        'operator',
        'value',
        'lookback_minutes',
        'sort_order',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'metric'           => PingAlertMetric::class,
            'operator'         => PingAlertOperator::class,
            'lookback_minutes' => 'integer',
            'sort_order'       => 'integer',
        ];
    }

    /** @return BelongsTo<PingAlertRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(PingAlertRule::class, 'ping_alert_rule_id');
    }

    /**
     * Evaluate this condition against live aggregate values for the target.
     *
     * @param array{avg_ms: float|null, max_ms: float|null, packet_loss: float|null, consecutive_failures: int} $metrics
     */
    public function evaluate(array $metrics): bool
    {
        $actual = match ($this->metric) {
            PingAlertMetric::LatencyAvg          => (float) ($metrics['avg_ms'] ?? 0),
            PingAlertMetric::LatencyMax          => (float) ($metrics['max_ms'] ?? 0),
            PingAlertMetric::PacketLoss          => (float) ($metrics['packet_loss'] ?? 0),
            PingAlertMetric::ConsecutiveFailures => (int) ($metrics['consecutive_failures'] ?? 0),
        };

        $threshold = (float) $this->value;

        return match ($this->operator) {
            PingAlertOperator::IsAbove        => $actual > $threshold,
            PingAlertOperator::IsBelow        => $actual < $threshold,
            PingAlertOperator::IsAboveOrEqual => $actual >= $threshold,
            PingAlertOperator::IsBelowOrEqual => $actual <= $threshold,
            PingAlertOperator::Is             => $actual == $threshold,
            PingAlertOperator::IsNot          => $actual != $threshold,
        };
    }
}
