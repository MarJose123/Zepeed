<?php

namespace App\Http\Resources;

use App\Models\AlertRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class AlertRuleResource extends JsonResource
{
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var AlertRule $rule */
        $rule = $this->resource;

        return [
            'id'                  => $rule->id,
            'name'                => $rule->name,
            'provider_slug'       => $rule->provider_slug,
            'event'               => $rule->event->value,
            'event_label'         => $rule->event->label(),
            'condition_operator'  => $rule->condition_operator,
            'is_active'           => $rule->is_active,
            'cooldown_minutes'    => $rule->cooldown_minutes,
            'last_triggered_at'   => $rule->last_triggered_at?->toIso8601String(),
            'conditions'          => $rule->conditions->map(fn ($c) => [
                'id'             => $c->id,
                'metric'         => $c->metric->value,
                'metric_label'   => $c->metric->label(),
                'operator'       => $c->operator->value,
                'operator_label' => $c->operator->label(),
                'value'          => $c->value,
                'sort_order'     => $c->sort_order,
            ])->values(),
            'actions' => $rule->actions->map(fn ($a) => [
                'id'                   => $a->id,
                'type'                 => $a->type,
                'mail_provider_id'     => $a->mail_provider_id,
                'mail_provider_label'  => $a->mailProvider?->label,
                'email_template_id'    => $a->email_template_id,
                'email_template_label' => $a->emailTemplate?->name,
                'recipient_email'      => $a->recipient_email,
                'webhook_id'           => $a->webhook_id,
                'webhook_label'        => $a->webhook?->name,
                'sort_order'           => $a->sort_order,
            ])->values(),
            'created_at' => $rule->created_at->toIso8601String(),
        ];
    }
}
