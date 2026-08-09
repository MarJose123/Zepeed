<?php

namespace App\Mcp\Tools;

use App\Enums\AlertRuleEvent;
use App\Enums\AlertRuleMetric;
use App\Enums\AlertRuleOperator;
use App\Enums\TokenAbility;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\AlertRule;
use App\Models\AlertRuleAction;
use App\Models\AlertRuleCondition;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('Update a speedtest alert rule by id. Conditions and actions are replaced wholesale when their keys are present. Requires the alerts:update token ability.')]
class UpdateAlertRule extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::AlertsUpdate);

        $validated = $request->validate([
            'id'                          => ['required', 'string'],
            'name'                        => ['sometimes', 'string', 'max:100'],
            'provider_slug'               => ['nullable', 'string'],
            'event'                       => ['sometimes', Rule::enum(AlertRuleEvent::class)],
            'condition_operator'          => ['sometimes', 'in:and,or'],
            'is_active'                   => ['boolean'],
            'cooldown_minutes'            => ['sometimes', 'integer', 'min:0', 'max:10080'],
            'conditions'                  => ['sometimes', 'array'],
            'conditions.*.metric'         => ['required_with:conditions', Rule::enum(AlertRuleMetric::class)],
            'conditions.*.operator'       => ['required_with:conditions', Rule::enum(AlertRuleOperator::class)],
            'conditions.*.value'          => ['required_with:conditions', 'string'],
            'conditions.*.sort_order'     => ['integer'],
            'actions'                     => ['sometimes', 'array', 'min:1'],
            'actions.*.type'              => ['required_with:actions', 'in:email,webhook,apprise'],
            'actions.*.mail_provider_id'  => ['nullable', 'uuid', 'exists:mail_providers,id'],
            'actions.*.email_template_id' => ['nullable', 'uuid', 'exists:email_templates,id'],
            'actions.*.recipient_email'   => ['nullable', 'email'],
            'actions.*.webhook_id'        => ['nullable', 'uuid', 'exists:webhooks,id'],
            'actions.*.apprise_id'        => ['nullable', 'uuid', 'exists:apprises,id'],
            'actions.*.sort_order'        => ['integer'],
        ]);

        $alertRule = AlertRule::query()->find($validated['id']);

        if ($alertRule === null) {
            return Response::error('Alert rule not found.');
        }

        unset($validated['id']);

        DB::transaction(static function () use ($validated, $alertRule): void {
            $updateData = [];
            foreach (['name', 'provider_slug', 'event', 'condition_operator', 'cooldown_minutes'] as $key) {
                if (array_key_exists($key, $validated)) {
                    $updateData[$key] = $validated[$key];
                }
            }
            if (array_key_exists('is_active', $validated)) {
                $updateData['is_active'] = $validated['is_active'];
            }

            if (! empty($updateData)) {
                $alertRule->update($updateData);
            }

            if (array_key_exists('conditions', $validated)) {
                $alertRule->conditions()->delete();

                foreach ($validated['conditions'] as $i => $condition) {
                    AlertRuleCondition::query()->create([
                        'alert_rule_id' => $alertRule->id,
                        'metric'        => $condition['metric'],
                        'operator'      => $condition['operator'],
                        'value'         => $condition['value'],
                        'sort_order'    => $condition['sort_order'] ?? $i,
                    ]);
                }
            }

            if (array_key_exists('actions', $validated)) {
                $alertRule->actions()->delete();

                foreach ($validated['actions'] as $i => $action) {
                    AlertRuleAction::query()->create([
                        'alert_rule_id'     => $alertRule->id,
                        'type'              => $action['type'],
                        'mail_provider_id'  => $action['mail_provider_id'] ?? null,
                        'email_template_id' => $action['email_template_id'] ?? null,
                        'recipient_email'   => $action['recipient_email'] ?? null,
                        'webhook_id'        => $action['webhook_id'] ?? null,
                        'apprise_id'        => $action['apprise_id'] ?? null,
                        'sort_order'        => $action['sort_order'] ?? $i,
                    ]);
                }
            }
        });

        $alertRule->load(['conditions', 'actions']);

        return Response::structured([
            'success'    => true,
            'message'    => 'Alert rule updated successfully.',
            'alert_rule' => $alertRule->refresh(),
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'id'                 => $schema->string()->description('Alert rule id (UUID).')->required(),
            'name'               => $schema->string()->description('Human-readable name.'),
            'provider_slug'      => $schema->string()->description('Provider slug: ookla, librespeed, netflix, cloudflare, or null for any.'),
            'event'              => $schema->string()->description('run_completes, run_fails, run_skipped, or any.')->enum(['run_completes', 'run_fails', 'run_skipped', 'any']),
            'condition_operator' => $schema->string()->description('and or or.')->enum(['and', 'or']),
            'is_active'          => $schema->boolean(),
            'cooldown_minutes'   => $schema->integer()->description('0-10080.')->min(0)->max(10080),
            'conditions'         => $schema->array()->items($schema->object([
                'metric'     => $schema->string()->description('status, download_mbps, upload_mbps, ping_ms, jitter_ms, or packet_loss.')->required(),
                'operator'   => $schema->string()->description('is, is_not, is_above, or is_below.')->required(),
                'value'      => $schema->string()->required(),
                'sort_order' => $schema->integer(),
            ])),
            'actions'            => $schema->array()->min(1)->items($schema->object([
                'type'              => $schema->string()->description('email, webhook or apprise.')->enum(['email', 'webhook', 'apprise'])->required(),
                'mail_provider_id'  => $schema->string()->description('UUID of a mail provider (email actions).'),
                'email_template_id' => $schema->string()->description('UUID of an email template (email actions).'),
                'recipient_email'   => $schema->string()->description('Recipient address (email actions).'),
                'webhook_id'        => $schema->string()->description('UUID of a webhook (webhook actions).'),
                'apprise_id'        => $schema->string()->description('UUID of an Apprise instance (apprise actions).'),
                'sort_order'        => $schema->integer(),
            ])),
        ];
    }
}
