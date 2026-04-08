<?php

namespace App\Http\Requests;

use App\Enums\AlertRuleEvent;
use App\Enums\AlertRuleMetric;
use App\Enums\AlertRuleOperator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlertRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:100'],
            'provider_slug'      => ['nullable', 'string'],
            'event'              => ['required', Rule::enum(AlertRuleEvent::class)],
            'condition_operator' => ['required', Rule::in(['and', 'or'])],
            'is_active'          => ['boolean'],
            'cooldown_minutes'   => ['integer', 'min:0', 'max:10080'],

            'conditions'               => ['array'],
            'conditions.*.metric'      => ['required', Rule::enum(AlertRuleMetric::class)],
            'conditions.*.operator'    => ['required', Rule::enum(AlertRuleOperator::class)],
            'conditions.*.value'       => ['required', 'string'],
            'conditions.*.sort_order'  => ['integer'],

            'actions'                        => ['array', 'min:1'],
            'actions.*.type'                 => ['required', Rule::in(['email', 'webhook'])],
            'actions.*.mail_provider_id'     => ['nullable', 'uuid', 'exists:mail_providers,id'],
            'actions.*.email_template_id'    => ['nullable', 'uuid', 'exists:email_templates,id'],
            'actions.*.recipient_email'      => ['nullable', 'email'],
            'actions.*.webhook_id'           => ['nullable', 'uuid', 'exists:webhooks,id'],
            'actions.*.sort_order'           => ['integer'],
        ];
    }
}
