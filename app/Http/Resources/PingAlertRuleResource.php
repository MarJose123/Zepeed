<?php

namespace App\Http\Resources;

use App\Models\PingAlertAction;
use App\Models\PingAlertCondition;
use App\Models\PingAlertRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class PingAlertRuleResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var PingAlertRule $rule */
        $rule = $this->resource;

        /** @var Collection<int, PingAlertCondition> $conditions */
        $conditions = $rule->relationLoaded('conditions')
            ? $rule->getRelation('conditions')
            : $rule->conditions()->get();

        /** @var Collection<int, PingAlertAction> $actions */
        $actions = $rule->relationLoaded('actions')
            ? $rule->getRelation('actions')
            : $rule->actions()->get();

        return [
            'id'                 => $rule->id,
            'name'               => $rule->name,
            'ping_target_id'     => $rule->ping_target_id,
            'target_label'       => $rule->relationLoaded('target') ? $rule->target->label : null,
            'target_host'        => $rule->relationLoaded('target') ? $rule->target->host : null,
            'condition_operator' => $rule->condition_operator,
            'is_active'          => $rule->is_active,
            'cooldown_minutes'   => $rule->cooldown_minutes,
            'last_triggered_at'  => $rule->last_triggered_at?->toIso8601String(),
            'conditions'         => $conditions->map(static fn ($c) => [
                'id'               => $c->id,
                'metric'           => $c->metric->value,
                'metric_label'     => $c->metric->label(),
                'metric_unit'      => $c->metric->unit(),
                'operator'         => $c->operator->value,
                'operator_label'   => $c->operator->label(),
                'value'            => $c->value,
                'lookback_minutes' => $c->lookback_minutes,
                'sort_order'       => $c->sort_order,
            ])->values(),
            'actions'            => $actions->map(static fn ($a) => [
                'id'                   => $a->id,
                'type'                 => $a->type,
                'mail_provider_id'     => $a->mail_provider_id,
                'mail_provider_label'  => $a->relationLoaded('mailProvider') ? $a->mailProvider?->label : null,
                'email_template_id'    => $a->email_template_id,
                'email_template_label' => $a->relationLoaded('emailTemplate') ? $a->emailTemplate?->name : null,
                'recipient_email'      => $a->recipient_email,
                'webhook_id'           => $a->webhook_id,
                'webhook_label'        => $a->relationLoaded('webhook') ? $a->webhook?->name : null,
                'sort_order'           => $a->sort_order,
            ])->values(),
            'created_at'         => $rule->created_at->toIso8601String(),
        ];
    }
}
