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
        'sort_order',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'metric'     => WorkflowRuleMetric::class,
            'operator'   => WorkflowRuleOperator::class,
            'sort_order' => 'integer',
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
        };

        return match ($this->operator) {
            WorkflowRuleOperator::Is      => (string) $actual === (string) $this->value,
            WorkflowRuleOperator::IsNot   => (string) $actual !== (string) $this->value,
            WorkflowRuleOperator::IsAbove => (float) $actual > (float) $this->value,
            WorkflowRuleOperator::IsBelow => (float) $actual < (float) $this->value,
        };
    }
}
