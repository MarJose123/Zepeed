<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePingAlertRuleRequest;
use App\Http\Requests\UpdatePingAlertRuleRequest;
use App\Http\Resources\Api\PingAlertRuleResource;
use App\Models\PingAlertAction;
use App\Models\PingAlertCondition;
use App\Models\PingAlertRule;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

#[Group(
    name: 'Ping Alerts',
    description: 'Manage alert rules for ping targets. Each rule watches a ping target and fires email/webhook actions when its conditions are met.',
    weight: 5,
)]
/**
 * Ping Alert Rule Endpoints
 *
 * Manage alert rules for ping targets. Each rule watches a ping target and
 * fires email/webhook actions when its conditions are met.
 */
class PingAlertRuleController extends Controller
{
    /**
     * List ping alert rules with their conditions and actions.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     * @queryParam is_active boolean Filter by active status (0 or 1).
     */
    #[Endpoint(title: 'List ping alert rules', description: 'List ping alert rules with their conditions and actions.')]
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(max((int) request()->query('per_page', 25), 1), 100);

        $rules = PingAlertRule::query()
            ->with(['conditions', 'actions'])
            ->when(
                request()->has('is_active'),
                static fn ($query) => $query->where('is_active', filter_var(request()->query('is_active'), FILTER_VALIDATE_BOOLEAN))
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return PingAlertRuleResource::collection($rules)->additional([
            'success' => filled($rules),
            'code'    => 200,
        ]);
    }

    /**
     * Show a single ping alert rule with its conditions and actions.
     *
     * @param PingAlertRule $pingAlertRule
     */
    #[Endpoint(title: 'Show ping alert rule', description: 'Show a single ping alert rule with its conditions and actions.')]
    public function show(PingAlertRule $pingAlertRule): JsonResource
    {
        $pingAlertRule->load(['conditions', 'actions']);

        return PingAlertRuleResource::make($pingAlertRule)->additional([
            'success' => true,
            'code'    => 200,
        ]);
    }

    /**
     * Create a ping alert rule with its conditions and actions.
     *
     * @param StorePingAlertRuleRequest $request
     */
    #[Endpoint(title: 'Create ping alert rule', description: 'Create a ping alert rule with its conditions and actions.')]
    public function store(StorePingAlertRuleRequest $request): JsonResponse
    {
        $rule = DB::transaction(static function () use ($request): PingAlertRule {
            $validated = $request->validated();

            $rule = PingAlertRule::query()->create([
                'name'               => $validated['name'],
                'ping_target_id'     => $validated['ping_target_id'],
                'condition_operator' => $validated['condition_operator'] ?? 'and',
                'is_active'          => $validated['is_active'] ?? true,
                'cooldown_minutes'   => $validated['cooldown_minutes'] ?? 30,
            ]);

            foreach ($validated['conditions'] as $i => $condition) {
                PingAlertCondition::query()->create([
                    'ping_alert_rule_id' => $rule->id,
                    'metric'             => $condition['metric'],
                    'operator'           => $condition['operator'],
                    'value'              => $condition['value'],
                    'lookback_minutes'   => $condition['lookback_minutes'] ?? 5,
                    'sort_order'         => $condition['sort_order'] ?? $i,
                ]);
            }

            foreach ($validated['actions'] as $i => $action) {
                PingAlertAction::query()->create([
                    'ping_alert_rule_id' => $rule->id,
                    'type'               => $action['type'],
                    'mail_provider_id'   => $action['mail_provider_id'] ?? null,
                    'email_template_id'  => $action['email_template_id'] ?? null,
                    'recipient_email'    => $action['recipient_email'] ?? null,
                    'webhook_id'         => $action['webhook_id'] ?? null,
                    'sort_order'         => $action['sort_order'] ?? $i,
                ]);
            }

            return $rule;
        });

        $rule->load(['conditions', 'actions']);

        return PingAlertRuleResource::make($rule)
            ->additional([
                'success' => true,
                'code'    => 201,
                'message' => 'Ping alert rule created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a ping alert rule. Conditions and actions are replaced
     * wholesale when their keys are present in the request.
     *
     * @param UpdatePingAlertRuleRequest $request
     * @param PingAlertRule              $pingAlertRule
     */
    #[Endpoint(title: 'Update ping alert rule', description: 'Update a ping alert rule. Conditions and actions are replaced wholesale when their keys are present in the request.')]
    public function update(UpdatePingAlertRuleRequest $request, PingAlertRule $pingAlertRule): JsonResource
    {
        DB::transaction(static function () use ($request, $pingAlertRule): void {
            $validated = $request->validated();

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

        return PingAlertRuleResource::make($pingAlertRule->refresh())->additional([
            'success' => true,
            'code'    => 200,
            'message' => 'Ping alert rule updated successfully.',
        ]);
    }

    /**
     * Delete a ping alert rule.
     *
     * @param PingAlertRule $pingAlertRule
     */
    #[Endpoint(title: 'Delete ping alert rule', description: 'Delete a ping alert rule.')]
    public function destroy(PingAlertRule $pingAlertRule): JsonResponse
    {
        $pingAlertRule->delete();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Ping alert rule deleted successfully.',
        ]);
    }

    /**
     * Toggle the active state of a ping alert rule.
     *
     * @param PingAlertRule $pingAlertRule
     */
    #[Endpoint(title: 'Toggle ping alert rule', description: 'Toggle the active state of a ping alert rule.')]
    public function toggle(PingAlertRule $pingAlertRule): JsonResource
    {
        $pingAlertRule->update(['is_active' => ! $pingAlertRule->is_active]);

        return PingAlertRuleResource::make($pingAlertRule->refresh())->additional([
            'success' => true,
            'code'    => 200,
            'message' => $pingAlertRule->is_active ? 'Ping alert rule activated.' : 'Ping alert rule paused.',
        ]);
    }
}
