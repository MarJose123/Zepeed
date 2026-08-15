<?php

namespace App\Http\Controllers;

use App\Enums\SpeedtestServer;
use App\Http\Requests\StoreWorkflowRuleRequest;
use App\Http\Requests\UpdateWorkflowRuleRequest;
use App\Http\Resources\AppriseResource;
use App\Http\Resources\EmailTemplateResource;
use App\Http\Resources\MailProviderResource;
use App\Http\Resources\PingTargetResource;
use App\Http\Resources\WebhookResource;
use App\Http\Resources\WorkflowRuleResource;
use App\Models\Apprise;
use App\Models\EmailTemplate;
use App\Models\MailProvider;
use App\Models\PingTarget;
use App\Models\Webhook;
use App\Models\WorkflowRule;
use App\Models\WorkflowRuleAction;
use App\Models\WorkflowRuleCondition;
use App\Services\InertiaNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WorkflowRuleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/WorkflowRules', [
            'rules' => WorkflowRuleResource::collection(
                WorkflowRule::query()
                    ->with(['target', 'conditions', 'actions.mailProvider', 'actions.emailTemplate', 'actions.webhook', 'actions.apprise'])
                    ->latest()
                    ->get()
            )->resolve(),

            // Use SpeedtestServer enum for proper labels
            'providers' => collect(SpeedtestServer::cases())
                ->map(static fn ($case) => [
                    'slug'  => $case->value,
                    'label' => $case->label(),
                ]),

            'targets' => PingTargetResource::collection(
                PingTarget::query()->orderBy('label')->get()
            )->resolve(),

            'mail_providers' => MailProviderResource::collection(
                MailProvider::query()->active()->ordered()->get()
            )->resolve(),

            'email_templates' => EmailTemplateResource::collection(
                EmailTemplate::query()
                    ->orderBy('name')
                    ->get()
            )->resolve(),

            'webhooks' => WebhookResource::collection(
                Webhook::query()->where('is_active', true)->latest()->get()
            )->resolve(),

            'apprises' => AppriseResource::collection(
                Apprise::query()->where('is_active', true)->latest()->get()
            )->resolve(),
        ]);
    }

    public function store(StoreWorkflowRuleRequest $request): RedirectResponse
    {
        $rule = DB::transaction(static function () use ($request): WorkflowRule {
            $validated = $request->validated();

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

        InertiaNotification::make()
            ->success()
            ->title('Workflow rule created')
            ->message("Rule \"{$request->validated('name')}\" is now active.")
            ->send();

        Inertia::flash('workflow_rule_id', $rule->id);

        return back();
    }

    public function update(
        UpdateWorkflowRuleRequest $request,
        WorkflowRule $workflowRule,
    ): RedirectResponse {
        DB::transaction(static function () use ($request, $workflowRule) {
            $validated = $request->validated();

            // Build update array explicitly — avoid array_filter stripping falsy values
            $updateData = [];
            foreach (['name', 'provider_slug', 'ping_target_id', 'event', 'condition_operator', 'cooldown_minutes'] as $key) {
                if (array_key_exists($key, $validated)) {
                    $updateData[$key] = $validated[$key];
                }
            }
            // is_active handled separately since false is a valid value
            if (array_key_exists('is_active', $validated)) {
                $updateData['is_active'] = $validated['is_active'];
            }

            if (! empty($updateData)) {
                $workflowRule->update($updateData);
            }

            // Always replace conditions wholesale when key is present
            if (array_key_exists('conditions', $validated)) {
                $workflowRule->conditions()->delete();

                foreach ($validated['conditions'] as $i => $condition) {
                    WorkflowRuleCondition::query()->create([
                        'workflow_rule_id' => $workflowRule->id,
                        'metric'           => $condition['metric'],
                        'operator'         => $condition['operator'],
                        'value'            => $condition['value'],
                        'lookback_minutes' => $condition['lookback_minutes'] ?? null,
                        'sort_order'       => $condition['sort_order'] ?? $i,
                    ]);
                }
            }

            // Always replace actions wholesale when key is present
            if (array_key_exists('actions', $validated)) {
                $workflowRule->actions()->delete();

                foreach ($validated['actions'] as $i => $action) {
                    WorkflowRuleAction::query()->create([
                        'workflow_rule_id'     => $workflowRule->id,
                        'type'                 => $action['type'],
                        'mail_provider_id'     => $action['mail_provider_id'] ?? null,
                        'email_template_id'    => $action['email_template_id'] ?? null,
                        'recipient_email'      => $action['recipient_email'] ?? null,
                        'webhook_id'           => $action['webhook_id'] ?? null,
                        'apprise_id'           => $action['apprise_id'] ?? null,
                        'sort_order'           => $action['sort_order'] ?? $i,
                    ]);
                }
            }
        });

        InertiaNotification::make()
            ->success()
            ->title('Workflow rule updated')
            ->message("\"{$workflowRule->name}\" has been updated.")
            ->send();

        Inertia::flash('workflow_rule_id', $workflowRule->id);

        return back();
    }

    public function destroy(WorkflowRule $workflowRule): RedirectResponse
    {
        $name = $workflowRule->name;
        $workflowRule->delete();

        InertiaNotification::make()
            ->success()
            ->title('Workflow rule deleted')
            ->message("\"{$name}\" has been removed.")
            ->send();

        return to_route('speedtest.workflow-rules.index');
    }

    public function toggle(WorkflowRule $workflowRule): RedirectResponse
    {
        $workflowRule->update(['is_active' => ! $workflowRule->is_active]);

        InertiaNotification::make()
            ->success()
            ->title($workflowRule->is_active ? 'Rule activated' : 'Rule paused')
            ->message("\"{$workflowRule->name}\" is now " . ($workflowRule->is_active ? 'active' : 'paused') . '.')
            ->send();

        return back();
    }
}
