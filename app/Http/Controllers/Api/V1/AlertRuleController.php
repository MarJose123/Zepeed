<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlertRuleRequest;
use App\Http\Requests\UpdateAlertRuleRequest;
use App\Http\Resources\Api\AlertRuleResource;
use App\Models\AlertRule;
use App\Models\AlertRuleAction;
use App\Models\AlertRuleCondition;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

#[Group(
    name: 'Speedtest Alerts',
    description: 'Manage speedtest alert rules. Each rule matches an event (e.g. run fails) against conditions and triggers actions (email and/or webhook) when they all evaluate to true.',
    weight: 9,
)]
/**
 * Alert Rule Endpoints
 *
 * Manage speedtest alert rules. Each rule matches an event (e.g. run fails)
 * against conditions and triggers actions (email and/or webhook) when they
 * all evaluate to true.
 */
class AlertRuleController extends Controller
{
    /**
     * List alert rules with their conditions and actions.
     *
     * @queryParam per_page int Default: 25. Max: 100. Minimum: 1.
     * @queryParam page int Default: 1. Current page number.
     * @queryParam is_active boolean Filter by active status (0 or 1).
     */
    #[Endpoint(title: 'List alert rules', description: 'List alert rules with their conditions and actions.')]
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(max((int) request()->query('per_page', 25), 1), 100);

        $rules = AlertRule::query()
            ->with(['conditions', 'actions'])
            ->when(
                request()->has('is_active'),
                static fn ($query) => $query->where('is_active', filter_var(request()->query('is_active'), FILTER_VALIDATE_BOOLEAN))
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return AlertRuleResource::collection($rules)->additional([
            'success' => filled($rules),
            'code'    => 200,
        ]);
    }

    /**
     * Show a single alert rule with its conditions and actions.
     *
     * @param AlertRule $alertRule
     */
    #[Endpoint(title: 'Show alert rule', description: 'Show a single alert rule with its conditions and actions.')]
    public function show(AlertRule $alertRule): JsonResource
    {
        $alertRule->load(['conditions', 'actions']);

        return AlertRuleResource::make($alertRule)->additional([
            'success' => true,
            'code'    => 200,
        ]);
    }

    /**
     * Create an alert rule with its conditions and actions.
     *
     * @param StoreAlertRuleRequest $request
     */
    #[Endpoint(title: 'Create alert rule', description: 'Create an alert rule with its conditions and actions.')]
    public function store(StoreAlertRuleRequest $request): JsonResponse
    {
        $rule = DB::transaction(static function () use ($request): AlertRule {
            $validated = $request->validated();

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
                    'apprise_id'        => $action['apprise_id'] ?? null,
                    'sort_order'        => $action['sort_order'] ?? $i,
                ]);
            }

            return $rule;
        });

        $rule->load(['conditions', 'actions']);

        return AlertRuleResource::make($rule)
            ->additional([
                'success' => true,
                'code'    => 201,
                'message' => 'Alert rule created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update an alert rule. Conditions and actions are replaced wholesale
     * when their keys are present in the request.
     *
     * @param UpdateAlertRuleRequest $request
     * @param AlertRule              $alertRule
     */
    #[Endpoint(title: 'Update alert rule', description: 'Update an alert rule. Conditions and actions are replaced wholesale when their keys are present in the request.')]
    public function update(UpdateAlertRuleRequest $request, AlertRule $alertRule): JsonResource
    {
        DB::transaction(static function () use ($request, $alertRule): void {
            $validated = $request->validated();

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

        return AlertRuleResource::make($alertRule->refresh())->additional([
            'success' => true,
            'code'    => 200,
            'message' => 'Alert rule updated successfully.',
        ]);
    }

    /**
     * Delete an alert rule.
     *
     * @param AlertRule $alertRule
     */
    #[Endpoint(title: 'Delete alert rule', description: 'Delete an alert rule.')]
    public function destroy(AlertRule $alertRule): JsonResponse
    {
        $alertRule->delete();

        return response()->json([
            'success' => true,
            'code'    => 200,
            'message' => 'Alert rule deleted successfully.',
        ]);
    }

    /**
     * Toggle the active state of an alert rule.
     *
     * @param AlertRule $alertRule
     */
    #[Endpoint(title: 'Toggle alert rule', description: 'Toggle the active state of an alert rule.')]
    public function toggle(AlertRule $alertRule): JsonResource
    {
        $alertRule->update(['is_active' => ! $alertRule->is_active]);

        return AlertRuleResource::make($alertRule->refresh())->additional([
            'success' => true,
            'code'    => 200,
            'message' => $alertRule->is_active ? 'Alert rule activated.' : 'Alert rule paused.',
        ]);
    }
}
