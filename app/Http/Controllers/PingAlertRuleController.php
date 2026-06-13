<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePingAlertRuleRequest;
use App\Http\Requests\UpdatePingAlertRuleRequest;
use App\Http\Resources\EmailTemplateResource;
use App\Http\Resources\MailProviderResource;
use App\Http\Resources\PingAlertRuleResource;
use App\Http\Resources\PingTargetResource;
use App\Http\Resources\WebhookResource;
use App\Models\EmailTemplate;
use App\Models\MailProvider;
use App\Models\PingAlertAction;
use App\Models\PingAlertCondition;
use App\Models\PingAlertRule;
use App\Models\PingTarget;
use App\Models\Webhook;
use App\Services\InertiaNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PingAlertRuleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('network/PingAlerts', [
            'rules' => PingAlertRuleResource::collection(
                PingAlertRule::query()
                    ->with(['target', 'conditions', 'actions.mailProvider', 'actions.emailTemplate', 'actions.webhook'])
                    ->latest()
                    ->get()
            )->resolve(),

            'targets' => PingTargetResource::collection(
                PingTarget::query()->orderBy('label')->get()
            )->resolve(),

            'mail_providers' => MailProviderResource::collection(
                MailProvider::query()->ordered()->get()
            )->resolve(),

            'email_templates' => EmailTemplateResource::collection(
                EmailTemplate::query()->orderBy('name')->get()
            )->resolve(),

            'webhooks' => WebhookResource::collection(
                Webhook::query()->where('is_active', true)->latest()->get()
            )->resolve(),
        ]);
    }

    public function store(StorePingAlertRuleRequest $request): RedirectResponse
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

        InertiaNotification::make()
            ->success()
            ->title('Alert rule created')
            ->message("\"{$rule->name}\" is now active.")
            ->send();

        Inertia::flash('ping_alert_rule_id', $rule->id);

        return back();
    }

    public function update(UpdatePingAlertRuleRequest $request, PingAlertRule $pingAlertRule): RedirectResponse
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

        InertiaNotification::make()
            ->success()
            ->title('Alert rule updated')
            ->message("\"{$pingAlertRule->name}\" has been updated.")
            ->send();

        Inertia::flash('ping_alert_rule_id', $pingAlertRule->id);

        return back();
    }

    public function destroy(PingAlertRule $pingAlertRule): RedirectResponse
    {
        $name = $pingAlertRule->name;
        $pingAlertRule->delete();

        InertiaNotification::make()
            ->success()
            ->title('Alert rule deleted')
            ->message("\"{$name}\" has been removed.")
            ->send();

        return to_route('speedtest.network.ping-alerts.index');
    }

    public function toggle(PingAlertRule $pingAlertRule): RedirectResponse
    {
        $pingAlertRule->update(['is_active' => ! $pingAlertRule->is_active]);

        InertiaNotification::make()
            ->success()
            ->title($pingAlertRule->is_active ? 'Rule activated' : 'Rule paused')
            ->message("\"{$pingAlertRule->name}\" is now " . ($pingAlertRule->is_active ? 'active' : 'paused') . '.')
            ->send();

        return back();
    }
}
