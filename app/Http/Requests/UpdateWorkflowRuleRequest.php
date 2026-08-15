<?php

namespace App\Http\Requests;

use App\Enums\WorkflowRuleEvent;
use App\Enums\WorkflowRuleMetric;
use App\Enums\WorkflowRuleOperator;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkflowRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'               => ['sometimes', 'string', 'max:255'],
            'provider_slug'      => ['nullable', 'string'],
            'event'              => ['sometimes', Rule::enum(WorkflowRuleEvent::class)],
            'ping_target_id'     => ['nullable', 'required_if:event,ping', 'uuid', 'exists:ping_targets,id'],
            'condition_operator' => ['sometimes', Rule::in(['and', 'or'])],
            'is_active'          => ['boolean'],
            'cooldown_minutes'   => ['sometimes', 'integer', 'min:0', 'max:10080'],

            'conditions'                      => ['sometimes', 'array'],
            'conditions.*.metric'             => ['required_with:conditions', Rule::enum(WorkflowRuleMetric::class), $this->metricMatchesEvent()],
            'conditions.*.operator'           => ['required_with:conditions', Rule::enum(WorkflowRuleOperator::class)],
            'conditions.*.value'              => ['required_with:conditions', 'string'],
            'conditions.*.lookback_minutes'   => ['nullable', 'integer', 'min:1', 'max:120'],
            'conditions.*.sort_order'         => ['integer'],

            'actions'                        => ['sometimes', 'array', 'min:1'],
            'actions.*.type'                 => ['required_with:actions', Rule::in(['email', 'webhook', 'apprise'])],
            'actions.*.mail_provider_id'     => ['nullable', 'uuid', 'exists:mail_providers,id'],
            'actions.*.email_template_id'    => ['nullable', 'uuid', 'exists:email_templates,id'],
            'actions.*.recipient_email'      => ['nullable', 'email'],
            'actions.*.webhook_id'           => ['nullable', 'uuid', 'exists:webhooks,id'],
            'actions.*.apprise_id'           => ['nullable', 'uuid', 'exists:apprises,id'],
            'actions.*.sort_order'           => ['integer'],
        ];
    }

    /**
     * Restrict condition metrics to the ones valid for the rule's event
     * (falls back to the current event when the request does not change it).
     */
    private function metricMatchesEvent(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $metric = WorkflowRuleMetric::tryFrom((string) $value);

            if ($metric === null) {
                return;
            }

            $rule = $this->route('workflowRule');
            $event = $this->input('event', $rule?->event?->value);

            if ($event === null) {
                return;
            }

            $isPing = $event === WorkflowRuleEvent::Ping->value;

            if ($isPing && ! $metric->isPingMetric()) {
                $fail('The selected metric is not valid for ping rules.');
            }

            if (! $isPing && $metric->isPingOnlyMetric()) {
                $fail('The selected metric is only valid for ping rules.');
            }
        };
    }
}
