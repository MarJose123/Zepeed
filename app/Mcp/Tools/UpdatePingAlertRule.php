<?php

namespace App\Mcp\Tools;

use App\Enums\PingAlertMetric;
use App\Enums\PingAlertOperator;
use App\Enums\TokenAbility;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\PingAlertAction;
use App\Models\PingAlertCondition;
use App\Models\PingAlertRule;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('Update a ping alert rule by id. Conditions and actions are replaced wholesale when their keys are present. Requires the ping-alerts:update token ability.')]
class UpdatePingAlertRule extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::PingAlertsUpdate);

        $validated = $request->validate([
            'id'                            => ['required', 'string'],
            'name'                          => ['sometimes', 'string', 'max:255'],
            'condition_operator'            => ['sometimes', 'in:and,or'],
            'is_active'                     => ['boolean'],
            'cooldown_minutes'              => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'conditions'                    => ['sometimes', 'array', 'min:1', 'max:5'],
            'conditions.*.metric'           => ['required', Rule::enum(PingAlertMetric::class)],
            'conditions.*.operator'         => ['required', Rule::enum(PingAlertOperator::class)],
            'conditions.*.value'            => ['required', 'numeric', 'min:0'],
            'conditions.*.lookback_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'conditions.*.sort_order'       => ['integer'],
            'actions'                       => ['sometimes', 'array', 'min:1', 'max:3'],
            'actions.*.type'                => ['required', 'in:email,webhook'],
            'actions.*.mail_provider_id'    => ['nullable', 'uuid', 'exists:mail_providers,id'],
            'actions.*.email_template_id'   => ['nullable', 'uuid', 'exists:email_templates,id'],
            'actions.*.recipient_email'     => ['nullable', 'email'],
            'actions.*.webhook_id'          => ['nullable', 'uuid', 'exists:webhooks,id'],
            'actions.*.sort_order'          => ['integer'],
        ]);

        $pingAlertRule = PingAlertRule::query()->find($validated['id']);

        if ($pingAlertRule === null) {
            return Response::error('Ping alert rule not found.');
        }

        unset($validated['id']);

        DB::transaction(static function () use ($validated, $pingAlertRule): void {
            $updateData = [];
            foreach (['name', 'condition_operator', 'cooldown_minutes'] as $key) {
                if (array_key_exists($key, $validated)) {
                    $updateData[$key] = $validated[$key];
                }
            }
            if (array_key_exists('is_active', $validated)) {
                $updateData['is_active'] = $validated['is_active'];
            }

            if (! empty($updateData)) {
                $pingAlertRule->update($updateData);
            }

            if (array_key_exists('conditions', $validated)) {
                $pingAlertRule->conditions()->delete();

                foreach ($validated['conditions'] as $i => $condition) {
                    PingAlertCondition::query()->create([
                        'ping_alert_rule_id' => $pingAlertRule->id,
                        'metric'             => $condition['metric'],
                        'operator'           => $condition['operator'],
                        'value'              => $condition['value'],
                        'lookback_minutes'   => $condition['lookback_minutes'] ?? 5,
                        'sort_order'         => $condition['sort_order'] ?? $i,
                    ]);
                }
            }

            if (array_key_exists('actions', $validated)) {
                $pingAlertRule->actions()->delete();

                foreach ($validated['actions'] as $i => $action) {
                    PingAlertAction::query()->create([
                        'ping_alert_rule_id' => $pingAlertRule->id,
                        'type'               => $action['type'],
                        'mail_provider_id'   => $action['mail_provider_id'] ?? null,
                        'email_template_id'  => $action['email_template_id'] ?? null,
                        'recipient_email'    => $action['recipient_email'] ?? null,
                        'webhook_id'         => $action['webhook_id'] ?? null,
                        'sort_order'         => $action['sort_order'] ?? $i,
                    ]);
                }
            }
        });

        $pingAlertRule->load(['conditions', 'actions']);

        return Response::structured([
            'success'         => true,
            'message'         => 'Ping alert rule updated successfully.',
            'ping_alert_rule' => $pingAlertRule->refresh(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'id'                 => $schema->string()->description('Ping alert rule id (UUID).')->required(),
            'name'               => $schema->string()->description('Human-readable name.'),
            'condition_operator' => $schema->string()->description('and or or.')->enum(['and', 'or']),
            'is_active'          => $schema->boolean(),
            'cooldown_minutes'   => $schema->integer()->description('1-1440.')->min(1)->max(1440),
            'conditions'         => $schema->array()->description('1-5 conditions.')->min(1)->max(5)->items($schema->object([
                'metric'           => $schema->string()->description('latency_avg, latency_max, packet_loss, or consecutive_failures.')->required(),
                'operator'         => $schema->string()->description('is_above, is_below, is_above_or_equal, is_below_or_equal, is, or is_not.')->required(),
                'value'            => $schema->number()->description('Numeric threshold.')->required(),
                'lookback_minutes' => $schema->integer()->description('1-120.')->required()->min(1)->max(120),
                'sort_order'       => $schema->integer(),
            ])),
            'actions'            => $schema->array()->description('1-3 actions.')->min(1)->max(3)->items($schema->object([
                'type'              => $schema->string()->description('email or webhook.')->enum(['email', 'webhook'])->required(),
                'mail_provider_id'  => $schema->string()->description('UUID of a mail provider (email actions).'),
                'email_template_id' => $schema->string()->description('UUID of an email template (email actions).'),
                'recipient_email'   => $schema->string()->description('Recipient address (email actions).'),
                'webhook_id'        => $schema->string()->description('UUID of a webhook (webhook actions).'),
                'sort_order'        => $schema->integer(),
            ])),
        ];
    }
}
