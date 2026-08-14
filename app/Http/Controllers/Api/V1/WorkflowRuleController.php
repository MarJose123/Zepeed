<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkflowRuleRequest;
use App\Http\Requests\UpdateWorkflowRuleRequest;
use App\Http\Resources\Api\WorkflowRuleResource;
use App\Models\WorkflowRule;
use App\Models\WorkflowRuleAction;
use App\Models\WorkflowRuleCondition;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

#[Group(
    name: 'Workflow Rules',
    description: 'Manage speedtest workflow rules. Each rule matches an event (e.g. run fails) against conditions and triggers actions (email and/or webhook) when they all evaluate to true.',
    weight: 9,
)]
/**
 * Workflow Rule Endpoints
 *
 * Manage speedtest workflow rules. Each rule matches an event (e.g. run fails)
 * against conditions and triggers actions (email and/or webhook) when they
 * all evaluate to true.
 */
class WorkflowRuleController extends Controller
{
    /**
     * List workflow rules with their conditions and actions.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     * @queryParam is_active boolean Filter by active status (0 or 1).
     */
    #[Endpoint(title: 'List workflow rules', description: 'List workflow rules with their conditions and actions.')]
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(max((int) request()->query('per_page', 25), 1), 100);

        $rules = WorkflowRule::query()
            ->with(['conditions', 'actions', 'target'])
            ->when(
                request()->has('is_active'),
                static fn ($query) => $query->where('is_active', filter_var(request()->query('is_active'), FILTER_VALIDATE_BOOLEAN))
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return WorkflowRuleResource::collection($rules)->additional([
            'success' => filled($rules),
            'code'    => 200,
        ]);
    }

    /**
     * Show a single workflow rule with its conditions and actions.
     *
     * @param WorkflowRule $workflowRule
     */
    #[Endpoint(title: 'Show workflow rule', description: 'Show a single workflow rule with its conditions and actions.')]
    public function show(WorkflowRule $workflowRule): JsonResource
    {
        $workflowRule->load(['conditions', 'actions', 'target']);

        return WorkflowRuleResource::make($workflowRule)->additional([
            'success' => true,
            'code'    => 200,
        ]);
    }

    /**
     * Create an workflow rule with its conditions and actions.
     *
     * @param StoreWorkflowRuleRequest $request
     */
    #[Endpoint(title: 'Create workflow rule', description: 'Create an workflow rule with its conditions and actions.')]
    public function store(StoreWorkflowRuleRequest $request): JsonResponse
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

        $rule->load(['conditions', 'actions', 'target']);

        return WorkflowRuleResource::make($rule)
            ->additional([
                'success' => true,
                'code'    => 201,
                'message' => 'Workflow rule created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an workflow rule. Conditions and actions are replaced wholesale
     * when their keys are present in the request.
     *
     * @param UpdateWorkflowRuleRequest $request
     * @param WorkflowRule              $workflowRule
     */
    #[Endpoint(title: 'Update workflow rule', description: 'Update an workflow rule. Conditions and actions are replaced wholesale when their keys are present in the request.')]
    public function update(UpdateWorkflowRuleRequest $request, WorkflowRule $workflowRule): JsonResource
    {
        DB::transaction(static function () use ($request, $workflowRule): void {
            $validated = $request->validated();

            $updateData = [];
            foreach (['name', 'provider_slug', 'ping_target_id', 'event', 'condition_operator', 'cooldown_minutes'] as $key) {
                if (array_key_exists($key, $validated)) {
                    $updateData[$key] = $validated[$key];
                }
            }
            if (array_key_exists('is_active', $validated)) {
                $updateData['is_active'] = $validated['is_active'];
            }

            if (! empty($updateData)) {
                $workflowRule->update($updateData);
            }

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

        $workflowRule->load(['conditions', 'actions', 'target']);

        return WorkflowRuleResource::make($workflowRule->refresh())->additional([
            'success' => true,
            'code'    => 200,
            'message' => 'Workflow rule updated successfully.',
        ]);
    }

    /**
     * Delete an workflow rule.
     *
     * @param WorkflowRule $workflowRule
     */
    #[Endpoint(title: 'Delete workflow rule', description: 'Delete an workflow rule.')]
    public function destroy(WorkflowRule $workflowRule): JsonResponse
    {
        $workflowRule->delete();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Workflow rule deleted successfully.',
        ]);
    }

    /**
     * Toggle the active state of an workflow rule.
     *
     * @param WorkflowRule $workflowRule
     */
    #[Endpoint(title: 'Toggle workflow rule', description: 'Toggle the active state of an workflow rule.')]
    public function toggle(WorkflowRule $workflowRule): JsonResource
    {
        $workflowRule->update(['is_active' => ! $workflowRule->is_active]);

        return WorkflowRuleResource::make($workflowRule->refresh())->additional([
            'success' => true,
            'code'    => 200,
            'message' => $workflowRule->is_active ? 'Workflow rule activated.' : 'Workflow rule paused.',
        ]);
    }
}
