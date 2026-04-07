<?php

namespace App\Http\Controllers;

use App\Enums\SpeedtestServer;
use App\Http\Requests\StoreAlertRuleRequest;
use App\Http\Requests\UpdateAlertRuleRequest;
use App\Http\Resources\AlertRuleResource;
use App\Http\Resources\EmailTemplateResource;
use App\Http\Resources\MailProviderResource;
use App\Http\Resources\WebhookResource;
use App\Models\AlertRule;
use App\Models\AlertRuleAction;
use App\Models\AlertRuleCondition;
use App\Models\EmailTemplate;
use App\Models\MailProvider;
use App\Models\Webhook;
use App\Services\InertiaNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AlertRuleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/AlertRules', [
            'rules' => AlertRuleResource::collection(
                AlertRule::query()
                    ->with(['conditions', 'actions.mailProvider', 'actions.emailTemplate', 'actions.webhook'])
                    ->latest()
                    ->get()
            )->resolve(),

            // Use SpeedtestServer enum for proper labels
            'providers' => collect(SpeedtestServer::cases())
                ->map(fn ($case) => [
                    'slug'  => $case->value,
                    'label' => $case->label(),
                ]),

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

    public function store(StoreAlertRuleRequest $request): RedirectResponse
    {
        $rule = DB::transaction(function () use ($request): AlertRule {
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
                    'sort_order'        => $action['sort_order'] ?? $i,
                ]);
            }

            return $rule;
        });

        InertiaNotification::make()
            ->success()
            ->title('Alert rule created')
            ->message("Rule \"{$request->validated('name')}\" is now active.")
            ->send();

        Inertia::flash('alert_rule_id', $rule->id);

        return back();
    }

    public function update(
        UpdateAlertRuleRequest $request,
        AlertRule $alertRule,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $alertRule) {
            $validated = $request->validated();

            // Build update array explicitly — avoid array_filter stripping falsy values
            $updateData = [];
            foreach (['name', 'provider_slug', 'event', 'condition_operator', 'cooldown_minutes'] as $key) {
                if (array_key_exists($key, $validated)) {
                    $updateData[$key] = $validated[$key];
                }
            }
            // is_active handled separately since false is a valid value
            if (array_key_exists('is_active', $validated)) {
                $updateData['is_active'] = $validated['is_active'];
            }

            if (! empty($updateData)) {
                $alertRule->update($updateData);
            }

            // Always replace conditions wholesale when key is present
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

            // Always replace actions wholesale when key is present
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
                        'sort_order'        => $action['sort_order'] ?? $i,
                    ]);
                }
            }
        });

        InertiaNotification::make()
            ->success()
            ->title('Alert rule updated')
            ->message("\"{$alertRule->name}\" has been updated.")
            ->send();

        Inertia::flash('alert_rule_id', $alertRule->id);

        return back();
    }

    public function destroy(AlertRule $alertRule): RedirectResponse
    {
        $name = $alertRule->name;
        $alertRule->delete();

        InertiaNotification::make()
            ->success()
            ->title('Alert rule deleted')
            ->message("\"{$name}\" has been removed.")
            ->send();

        return to_route('speedtest.alert-rules.index');
    }

    public function toggle(AlertRule $alertRule): RedirectResponse
    {
        $alertRule->update(['is_active' => ! $alertRule->is_active]);

        InertiaNotification::make()
            ->success()
            ->title($alertRule->is_active ? 'Rule activated' : 'Rule paused')
            ->message("\"{$alertRule->name}\" is now ".($alertRule->is_active ? 'active' : 'paused').'.')
            ->send();

        return back();
    }
}
