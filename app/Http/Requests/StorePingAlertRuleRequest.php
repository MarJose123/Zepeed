<?php

namespace App\Http\Requests;

use App\Enums\PingAlertMetric;
use App\Enums\PingAlertOperator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePingAlertRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'ping_target_id'     => ['required', 'uuid', 'exists:ping_targets,id'],
            'condition_operator' => ['required', Rule::in(['and', 'or'])],
            'is_active'          => ['boolean'],
            'cooldown_minutes'   => ['required', 'integer', 'min:1', 'max:1440'],

            'conditions'                     => ['required', 'array', 'min:1', 'max:5'],
            'conditions.*.metric'            => ['required', Rule::enum(PingAlertMetric::class)],
            'conditions.*.operator'          => ['required', Rule::enum(PingAlertOperator::class)],
            'conditions.*.value'             => ['required', 'numeric', 'min:0'],
            'conditions.*.lookback_minutes'  => ['required', 'integer', 'min:1', 'max:120'],
            'conditions.*.sort_order'        => ['integer'],

            'actions'                     => ['required', 'array', 'min:1', 'max:3'],
            'actions.*.type'              => ['required', Rule::in(['email', 'webhook'])],
            'actions.*.mail_provider_id'  => ['nullable', 'uuid', 'exists:mail_providers,id'],
            'actions.*.email_template_id' => ['nullable', 'uuid', 'exists:email_templates,id'],
            'actions.*.recipient_email'   => ['nullable', 'email'],
            'actions.*.webhook_id'        => ['nullable', 'uuid', 'exists:webhooks,id'],
            'actions.*.sort_order'        => ['integer'],
        ];
    }
}
