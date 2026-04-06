<?php

namespace App\Models;

use App\Enums\AlertRuleMetric;
use App\Enums\AlertRuleOperator;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property string            $id
 * @property string            $alert_rule_id
 * @property AlertRuleMetric   $metric
 * @property AlertRuleOperator $operator
 * @property string            $value
 * @property int               $sort_order
 */
class AlertRuleCondition extends Model
{
    use HasUuids;

    protected $fillable = [
        'alert_rule_id',
        'metric',
        'operator',
        'value',
        'sort_order',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'metric'     => AlertRuleMetric::class,
            'operator'   => AlertRuleOperator::class,
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<AlertRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'alert_rule_id');
    }

    /**
     * Evaluate this condition against a SpeedResult.
     */
    public function evaluate(SpeedResult $result): bool
    {
        $actual = match ($this->metric) {
            AlertRuleMetric::Status       => $result->status,
            AlertRuleMetric::DownloadMbps => (float) $result->download_mbps,
            AlertRuleMetric::UploadMbps   => (float) $result->upload_mbps,
            AlertRuleMetric::PingMs       => (float) $result->ping_ms,
            AlertRuleMetric::JitterMs     => (float) $result->jitter_ms,
            AlertRuleMetric::PacketLoss   => (float) $result->packet_loss,
        };

        return match ($this->operator) {
            AlertRuleOperator::Is      => (string) $actual === (string) $this->value,
            AlertRuleOperator::IsNot   => (string) $actual !== (string) $this->value,
            AlertRuleOperator::IsAbove => (float) $actual > (float) $this->value,
            AlertRuleOperator::IsBelow => (float) $actual < (float) $this->value,
        };
    }
}
