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

#[Description('Create a speedtest alert rule with its conditions and actions. Each rule matches an event (e.g. run fails) against conditions and triggers actions (email and/or webhook) when they all evaluate to true. Requires the alerts:create token ability.')]
class CreateAlertRule extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::AlertsCreate);

        $validated = $request->validate([
            'name'                        => ['required', 'string', 'max:100'],
            'provider_slug'               => ['nullable', 'string'],
            'event'                       => ['required', Rule::enum(AlertRuleEvent::class)],
            'condition_operator'          => ['required', 'in:and,or'],
            'is_active'                   => ['boolean'],
            'cooldown_minutes'            => ['integer', 'min:0', 'max:10080'],
            'conditions'                  => ['array'],
            'conditions.*.metric'         => ['required_with:conditions', Rule::enum(AlertRuleMetric::class)],
            'conditions.*.operator'       => ['required_with:conditions', Rule::enum(AlertRuleOperator::class)],
            'conditions.*.value'          => ['required_with:conditions', 'string'],
            'conditions.*.sort_order'     => ['integer'],
            'actions'                     => ['array', 'min:1'],
            'actions.*.type'              => ['required_with:actions', 'in:email,webhook'],
            'actions.*.mail_provider_id'  => ['nullable', 'uuid', 'exists:mail_providers,id'],
            'actions.*.email_template_id' => ['nullable', 'uuid', 'exists:email_templates,id'],
            'actions.*.recipient_email'   => ['nullable', 'email'],
            'actions.*.webhook_id'        => ['nullable', 'uuid', 'exists:webhooks,id'],
            'actions.*.sort_order'        => ['integer'],
        ]);

        $rule = DB::transaction(static function () use ($validated): AlertRule {
            $rule = AlertRule::query()->create([
                'name'               => $validated['name'],
                'provider_slug'      => $validated['provider_slug'] ?? null,
                'event'              => $validated['event'],
                'condition_operator' => $validated['condition_operator'] ?? 'and',
                'is_active'          => $validated['is_active'] ?? true,
                'cooldown_minutes'   => $validated['cooldown_minutes'] ?? 30,
            ]);

            foreach ($validated['conditions'] ?? [] as $i => $condition) {
                AlertRuleCondition::query()->create([
                    'alert_rule_id' => $rule->id,
                    'metric'        => $condition['metric'],
                    'operator'      => $condition['operator'],
                    'value'         => $condition['value'],
                    'sort_order'    => $condition['sort_order'] ?? $i,
                ]);
            }

            foreach ($validated['actions'] ?? [] as $i => $action) {
                AlertRuleAction::query()->create([
                    'alert_rule_id'     => $rule->id,
                    'type'              => $action['type'],
                    'mail_provider_id'  => $action['mail_provider_id'] ?? null,
                    'email_template_id' => $action['email_template_id'] ?? null,
                    'recipient_email'   => $action['recipient_email'] ?? null,
                    'webhook_id'        => $action['webhook_id'] ?? null,
                    'sort_order'        => $action['sort_order'] ?? $i,
                ]);
            }

            return $rule;
        });

        $rule->load(['conditions', 'actions']);

        return Response::structured([
            'success'    => true,
            'message'    => 'Alert rule created successfully.',
            'alert_rule' => $rule,
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'name'               => $schema->string()->description('Human-readable name.')->required(),
            'provider_slug'      => $schema->string()->description('Provider slug: ookla, librespeed, netflix, cloudflare, or null for any.'),
            'event'              => $schema->string()->description('run_completes, run_fails, run_skipped, or any.')->enum(['run_completes', 'run_fails', 'run_skipped', 'any'])->required(),
            'condition_operator' => $schema->string()->description('and or or.')->enum(['and', 'or'])->default('and'),
            'is_active'          => $schema->boolean()->default(true),
            'cooldown_minutes'   => $schema->integer()->description('0-10080.')->default(30)->min(0)->max(10080),
            'conditions'         => $schema->array()->items($schema->object([
                'metric'     => $schema->string()->description('status, download_mbps, upload_mbps, ping_ms, jitter_ms, or packet_loss.')->required(),
                'operator'   => $schema->string()->description('is, is_not, is_above, or is_below.')->required(),
                'value'      => $schema->string()->required(),
                'sort_order' => $schema->integer(),
            ])),
            'actions'            => $schema->array()->min(1)->items($schema->object([
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
