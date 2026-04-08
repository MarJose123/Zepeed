<?php

namespace App\Http\Requests;

use App\Enums\AlertRuleEvent;
use App\Enums\AlertRuleMetric;
use App\Enums\AlertRuleOperator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlertRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'               => ['sometimes', 'string', 'max:100'],
            'provider_slug'      => ['nullable', 'string'],
            'event'              => ['sometimes', Rule::enum(AlertRuleEvent::class)],
            'condition_operator' => ['sometimes', Rule::in(['and', 'or'])],
            'is_active'          => ['boolean'],
            'cooldown_minutes'   => ['sometimes', 'integer', 'min:0', 'max:10080'],

            'conditions'               => ['sometimes', 'array'],
            'conditions.*.metric'      => ['required_with:conditions', Rule::enum(AlertRuleMetric::class)],
            'conditions.*.operator'    => ['required_with:conditions', Rule::enum(AlertRuleOperator::class)],
            'conditions.*.value'       => ['required_with:conditions', 'string'],
            'conditions.*.sort_order'  => ['integer'],

            'actions'                        => ['sometimes', 'array', 'min:1'],
            'actions.*.type'                 => ['required_with:actions', Rule::in(['email', 'webhook'])],
            'actions.*.mail_provider_id'     => ['nullable', 'uuid', 'exists:mail_providers,id'],
            'actions.*.email_template_id'    => ['nullable', 'uuid', 'exists:email_templates,id'],
            'actions.*.recipient_email'      => ['nullable', 'email'],
            'actions.*.webhook_id'           => ['nullable', 'uuid', 'exists:webhooks,id'],
            'actions.*.sort_order'           => ['integer'],
        ];
    }
}
