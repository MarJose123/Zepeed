<?php

namespace App\Http\Resources\Api;

use App\Models\WorkflowRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin WorkflowRule
 */
class WorkflowRuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var WorkflowRule $rule */
        $rule = $this->resource;

        return [
            'id'                 => $rule->id,
            'name'               => $rule->name,
            'provider_slug'      => $rule->provider_slug,
            'ping_target_id'     => $rule->ping_target_id,
            'target_label'       => $rule->relationLoaded('target') ? $rule->target?->label : null,
            'target_host'        => $rule->relationLoaded('target') ? $rule->target?->host : null,
            'event'              => $rule->event->value,
            'event_label'        => $rule->event->label(),
            'condition_operator' => $rule->condition_operator,
            'is_active'          => $rule->is_active,
            'cooldown_minutes'   => $rule->cooldown_minutes,
            'last_triggered_at'  => $rule->last_triggered_at?->toIso8601String(),
            'conditions'         => $rule->conditions()->get()->map(static fn ($condition): array => [
                'id'               => $condition->id,
                'metric'           => $condition->metric->value,
                'operator'         => $condition->operator->value,
                'value'            => $condition->value,
                'lookback_minutes' => $condition->lookback_minutes,
                'sort_order'       => $condition->sort_order,
            ])->values(),
            'actions' => $rule->actions()->get()->map(static fn ($action): array => [
                'id'                 => $action->id,
                'type'               => $action->type,
                'mail_provider_id'   => $action->mail_provider_id,
                'email_template_id'  => $action->email_template_id,
                'recipient_email'    => $action->recipient_email,
                'webhook_id'         => $action->webhook_id,
                'apprise_id'         => $action->apprise_id,
                'sort_order'         => $action->sort_order,
            ])->values(),
            'created_at' => $rule->created_at->toIso8601String(),
        ];
    }
}
