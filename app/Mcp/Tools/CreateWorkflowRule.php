<?php

namespace App\Mcp\Tools;

use App\Enums\TokenAbility;
use App\Enums\WorkflowRuleEvent;
use App\Enums\WorkflowRuleMetric;
use App\Enums\WorkflowRuleOperator;
use App\Mcp\Tools\Concerns\AuthorizesRequests;
use App\Models\WorkflowRule;
use App\Models\WorkflowRuleAction;
use App\Models\WorkflowRuleCondition;
use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Override;

#[Description('Create a workflow rule with its conditions and actions. A speedtest rule matches an event (e.g. run fails) against conditions; a ping rule (event "ping") watches a ping target. Triggers actions (email, webhook and/or apprise) when they all evaluate to true. Requires the workflow-rules:create token ability.')]
class CreateWorkflowRule extends Tool
{
    use AuthorizesRequests;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize($request, TokenAbility::WorkflowRulesCreate);

        $validated = $request->validate([
            'name'                          => ['required', 'string', 'max:255'],
            'provider_slug'                 => ['nullable', 'string'],
            'event'                         => ['required', Rule::enum(WorkflowRuleEvent::class)],
            'ping_target_id'                => ['nullable', 'required_if:event,ping', 'uuid', 'exists:ping_targets,id'],
            'condition_operator'            => ['required', 'in:and,or'],
            'is_active'                     => ['boolean'],
            'cooldown_minutes'              => ['integer', 'min:0', 'max:10080'],
            'conditions'                    => ['array'],
            'conditions.*.metric'           => ['required_with:conditions', Rule::enum(WorkflowRuleMetric::class), $this->metricMatchesEvent($request)],
            'conditions.*.operator'         => ['required_with:conditions', Rule::enum(WorkflowRuleOperator::class)],
            'conditions.*.value'            => ['required_with:conditions', 'string'],
            'conditions.*.lookback_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
            'conditions.*.sort_order'       => ['integer'],
            'actions'                       => ['array', 'min:1'],
            'actions.*.type'                => ['required_with:actions', 'in:email,webhook,apprise'],
            'actions.*.mail_provider_id'    => ['nullable', 'uuid', 'exists:mail_providers,id'],
            'actions.*.email_template_id'   => ['nullable', 'uuid', 'exists:email_templates,id'],
            'actions.*.recipient_email'     => ['nullable', 'email'],
            'actions.*.webhook_id'          => ['nullable', 'uuid', 'exists:webhooks,id'],
            'actions.*.apprise_id'          => ['nullable', 'uuid', 'exists:apprises,id'],
            'actions.*.sort_order'          => ['integer'],
        ]);

        $rule = DB::transaction(static function () use ($validated): WorkflowRule {
            $rule = WorkflowRule::query()->create([
                'name'               => $validated['name'],
                'provider_slug'      => $validated['provider_slug'] ?? null,
                'ping_target_id'     => $validated['ping_target_id'] ?? null,
                'event'              => $validated['event'],
                'condition_operator' => $validated['condition_operator'] ?? 'and',
                'is_active'          => $validated['is_active'] ?? true,
                'cooldown_minutes'   => $validated['cooldown_minutes'] ?? 30,
            ]);

            foreach ($validated['conditions'] ?? [] as $i => $condition) {
                WorkflowRuleCondition::query()->create([
                    'workflow_rule_id' => $rule->id,
                    'metric'           => $condition['metric'],
                    'operator'         => $condition['operator'],
                    'value'            => $condition['value'],
                    'lookback_minutes' => $condition['lookback_minutes'] ?? null,
                    'sort_order'       => $condition['sort_order'] ?? $i,
                ]);
            }

            foreach ($validated['actions'] ?? [] as $i => $action) {
                WorkflowRuleAction::query()->create([
                    'workflow_rule_id'     => $rule->id,
                    'type'                 => $action['type'],
                    'mail_provider_id'     => $action['mail_provider_id'] ?? null,
                    'email_template_id'    => $action['email_template_id'] ?? null,
                    'recipient_email'      => $action['recipient_email'] ?? null,
                    'webhook_id'           => $action['webhook_id'] ?? null,
                    'apprise_id'           => $action['apprise_id'] ?? null,
                    'sort_order'           => $action['sort_order'] ?? $i,
                ]);
            }

            return $rule;
        });

        $rule->load(['conditions', 'actions', 'target']);

        return Response::structured([
            'success'       => true,
            'message'       => 'Workflow rule created successfully.',
            'workflow_rule' => $rule,
        ]);
    }

    /**
     * Restrict condition metrics to the ones valid for the rule's event.
     */
    private function metricMatchesEvent(Request $request): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($request): void {
            $metric = WorkflowRuleMetric::tryFrom((string) $value);

            if ($metric === null) {
                return;
            }

            $isPing = $request->get('event') === WorkflowRuleEvent::Ping->value;

            if ($isPing && ! $metric->isPingMetric()) {
                $fail('The selected metric is not valid for ping rules.');
            }

            if (! $isPing && $metric->isPingOnlyMetric()) {
                $fail('The selected metric is only valid for ping rules.');
            }
        };
    }

    /**
     * Get the tool's input schema.
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'name'               => $schema->string()->description('Human-readable name.')->required(),
            'provider_slug'      => $schema->string()->description('Provider slug: ookla, librespeed, netflix, cloudflare, or null for any (speedtest rules only).'),
            'event'              => $schema->string()->description('run_completes, run_fails, run_skipped, any, or ping.')->enum(['run_completes', 'run_fails', 'run_skipped', 'any', 'ping'])->required(),
            'ping_target_id'     => $schema->string()->description('UUID of the ping target to watch (required when event is ping).'),
            'condition_operator' => $schema->string()->description('and or or.')->enum(['and', 'or'])->default('and'),
            'is_active'          => $schema->boolean()->default(true),
            'cooldown_minutes'   => $schema->integer()->description('0-10080.')->default(30)->min(0)->max(10080),
            'conditions'         => $schema->array()->items($schema->object([
                'metric'           => $schema->string()->description('Speedtest: status, download_mbps, upload_mbps, ping_ms, jitter_ms, or packet_loss. Ping: latency_avg, latency_max, packet_loss, or consecutive_failures.')->required(),
                'operator'         => $schema->string()->description('is, is_not, is_above, is_below, is_above_or_equal, or is_below_or_equal.')->required(),
                'value'            => $schema->string()->required(),
                'lookback_minutes' => $schema->integer()->description('Lookback window in minutes (ping rules only, 1-120).')->min(1)->max(120),
                'sort_order'       => $schema->integer(),
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
