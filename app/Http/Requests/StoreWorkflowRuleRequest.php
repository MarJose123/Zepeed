<?php

namespace App\Http\Requests;

use App\Enums\WorkflowRuleEvent;
use App\Enums\WorkflowRuleMetric;
use App\Enums\WorkflowRuleOperator;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkflowRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'provider_slug'      => ['nullable', 'string'],
            'event'              => ['required', Rule::enum(WorkflowRuleEvent::class)],
            'ping_target_id'     => ['nullable', 'required_if:event,ping', 'uuid', 'exists:ping_targets,id'],
            'condition_operator' => ['required', Rule::in(['and', 'or'])],
            'is_active'          => ['boolean'],
            'cooldown_minutes'   => ['integer', 'min:0', 'max:10080'],

            'conditions'                      => ['array'],
            'conditions.*.metric'             => ['required', Rule::enum(WorkflowRuleMetric::class), $this->metricMatchesEvent()],
            'conditions.*.operator'           => ['required', Rule::enum(WorkflowRuleOperator::class)],
            'conditions.*.value'              => ['required', 'string'],
            'conditions.*.lookback_minutes'   => ['nullable', 'integer', 'min:1', 'max:120'],
            'conditions.*.sort_order'         => ['integer'],

            'actions'                        => ['array', 'min:1'],
            'actions.*.type'                 => ['required', Rule::in(['email', 'webhook', 'apprise'])],
            'actions.*.mail_provider_id'     => ['nullable', 'uuid', 'exists:mail_providers,id'],
            'actions.*.email_template_id'    => ['nullable', 'uuid', 'exists:email_templates,id'],
            'actions.*.recipient_email'      => ['nullable', 'email'],
            'actions.*.webhook_id'           => ['nullable', 'uuid', 'exists:webhooks,id'],
            'actions.*.apprise_id'           => ['nullable', 'uuid', 'exists:apprises,id'],
            'actions.*.sort_order'           => ['integer'],
        ];
    }

    /**
     * Restrict condition metrics to the ones valid for the rule's event:
     * ping metrics for ping rules, speedtest metrics otherwise.
     */
    private function metricMatchesEvent(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $metric = WorkflowRuleMetric::tryFrom((string) $value);

            if ($metric === null) {
                return;
            }

            $isPing = $this->input('event') === WorkflowRuleEvent::Ping->value;

            if ($isPing && ! $metric->isPingMetric()) {
                $fail('The selected metric is not valid for ping rules.');
            }

            if (! $isPing && $metric->isPingOnlyMetric()) {
                $fail('The selected metric is only valid for ping rules.');
            }
        };
    }
}
