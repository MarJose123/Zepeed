<?php

namespace App\Models;

use App\Enums\WorkflowRuleMetric;
use App\Enums\WorkflowRuleOperator;
use Database\Factories\WorkflowRuleConditionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property string               $id
 * @property string               $workflow_rule_id
 * @property WorkflowRuleMetric   $metric
 * @property WorkflowRuleOperator $operator
 * @property string               $value
 * @property int|null             $lookback_minutes
 * @property int                  $sort_order
 */
#[UseFactory(WorkflowRuleConditionFactory::class)]
class WorkflowRuleCondition extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'workflow_rule_id',
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
            'metric'           => WorkflowRuleMetric::class,
            'operator'         => WorkflowRuleOperator::class,
            'lookback_minutes' => 'integer',
            'sort_order'       => 'integer',
        ];
    }

    /** @return BelongsTo<WorkflowRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(WorkflowRule::class, 'workflow_rule_id');
    }

    /**
     * Evaluate this condition against a SpeedResult.
     */
    public function evaluate(SpeedResult $result): bool
    {
        $actual = match ($this->metric) {
            WorkflowRuleMetric::Status       => $result->status,
            WorkflowRuleMetric::DownloadMbps => (float) $result->download_mbps,
            WorkflowRuleMetric::UploadMbps   => (float) $result->upload_mbps,
            WorkflowRuleMetric::PingMs       => (float) $result->ping_ms,
            WorkflowRuleMetric::JitterMs     => (float) $result->jitter_ms,
            WorkflowRuleMetric::PacketLoss   => (float) $result->packet_loss,
            // Ping-only metrics are not applicable to speedtest results.
            WorkflowRuleMetric::LatencyAvg,
            WorkflowRuleMetric::LatencyMax,
            WorkflowRuleMetric::ConsecutiveFailures => null,
        };

        if ($actual === null) {
            return false;
        }

        return $this->compare($actual);
    }

    /**
     * Evaluate this condition against live aggregate values for a ping target.
     *
     * @param array{avg_ms: float|null, max_ms: float|null, packet_loss: float|null, consecutive_failures: int} $metrics
     */
    public function evaluatePing(array $metrics): bool
    {
        $actual = match ($this->metric) {
            WorkflowRuleMetric::LatencyAvg          => (float) ($metrics['avg_ms'] ?? 0),
            WorkflowRuleMetric::LatencyMax          => (float) ($metrics['max_ms'] ?? 0),
            WorkflowRuleMetric::PacketLoss          => (float) ($metrics['packet_loss'] ?? 0),
            WorkflowRuleMetric::ConsecutiveFailures => (int) ($metrics['consecutive_failures'] ?? 0),
            // Speedtest-only metrics are not applicable to ping results.
            WorkflowRuleMetric::Status,
            WorkflowRuleMetric::DownloadMbps,
            WorkflowRuleMetric::UploadMbps,
            WorkflowRuleMetric::PingMs,
            WorkflowRuleMetric::JitterMs => null,
        };

        if ($actual === null) {
            return false;
        }

        return $this->compare($actual);
    }

    private function compare(float|string $actual): bool
    {
        return match ($this->operator) {
            WorkflowRuleOperator::Is             => (string) $actual === (string) $this->value,
            WorkflowRuleOperator::IsNot          => (string) $actual !== (string) $this->value,
            WorkflowRuleOperator::IsAbove        => (float) $actual > (float) $this->value,
            WorkflowRuleOperator::IsBelow        => (float) $actual < (float) $this->value,
            WorkflowRuleOperator::IsAboveOrEqual => (float) $actual >= (float) $this->value,
            WorkflowRuleOperator::IsBelowOrEqual => (float) $actual <= (float) $this->value,
        };
    }
}
