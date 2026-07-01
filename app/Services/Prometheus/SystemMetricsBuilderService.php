<?php

namespace App\Services\Prometheus;

use App\Models\AlertRule;
use App\Models\MaintenanceWindow;
use App\Models\PingAlertRule;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Prometheus\CollectorRegistry;

class SystemMetricsBuilderService
{
    /**
     * Register Groups 6, 7, 8, 9 — alert rules, maintenance,
     * app info, and webhook delivery health.
     */
    public function register(CollectorRegistry $registry): void
    {
        $this->registerAlertRules($registry);
        $this->registerMaintenance($registry);
        $this->registerWebhooks($registry);
        $this->registerAppInfo($registry);
    }

    /**
     * Group 6 — Speedtest and ping alert rule active state + last triggered.
     */
    private function registerAlertRules(CollectorRegistry $registry): void
    {
        $speedRules = AlertRule::query()
            ->get(['name', 'provider_slug', 'event', 'is_active', 'last_triggered_at']);

        $pingRules = PingAlertRule::query()
            ->with('target:id,label')
            ->get(['id', 'name', 'ping_target_id', 'is_active', 'last_triggered_at']);

        $srActive = $registry->registerGauge('zepeed', 'alert_rule_active', '1 if the speedtest alert rule is active', ['name', 'event', 'provider']);
        $srTs = $registry->registerGauge('zepeed', 'alert_rule_last_triggered_timestamp', 'Unix epoch of last trigger, 0 if never fired', ['name', 'provider']);
        $prActive = $registry->registerGauge('zepeed', 'ping_alert_rule_active', '1 if the ping alert rule is active', ['name', 'target']);
        $prTs = $registry->registerGauge('zepeed', 'ping_alert_rule_last_triggered_timestamp', 'Unix epoch of last trigger, 0 if never fired', ['name', 'target']);

        foreach ($speedRules as $rule) {
            $provider = $rule->provider_slug ?? 'any';
            $firedTs = $rule->last_triggered_at?->getTimestamp() ?? 0;

            $srActive->set($rule->is_active ? 1.0 : 0.0, [(string) $rule->name, (string) $rule->event->value, (string) $provider]);
            $srTs->set((float) $firedTs, [(string) $rule->name, (string) $provider]);
        }

        foreach ($pingRules as $rule) {
            $firedTs = $rule->last_triggered_at?->getTimestamp() ?? 0;
            $targetLabel = $rule->target->label;

            $prActive->set($rule->is_active ? 1.0 : 0.0, [(string) $rule->name, (string) $targetLabel]);
            $prTs->set((float) $firedTs, [(string) $rule->name, (string) $targetLabel]);
        }
    }

    /**
     * Group 7 — Maintenance window state.
     *
     * The speedtest global pause is a MaintenanceWindow row with
     * type = Indefinite and providers = ['all']. isCurrentlyActive()
     * returns true for it, so it is already captured by the query below.
     *
     * Setting::get('maintenance_enabled') is the web-level 503 mode and
     * is unrelated to speedtest pausing — it must not be used here.
     */
    private function registerMaintenance(CollectorRegistry $registry): void
    {
        $windows = MaintenanceWindow::query()->where('is_active', true)->get();
        $anyActive = $windows->contains(fn (MaintenanceWindow $w) => $w->isCurrentlyActive());

        $active = $registry->registerGauge('zepeed', 'maintenance_active', '1 if any speedtest maintenance window is currently active', []);
        $wTotal = $registry->registerGauge('zepeed', 'maintenance_windows_total', 'Count of maintenance_windows rows with is_active=1', []);

        $active->set($anyActive ? 1.0 : 0.0, []);
        $wTotal->set((float) $windows->count(), []);
    }

    /**
     * Group 9 — Webhook active state and 24h delivery health.
     */
    private function registerWebhooks(CollectorRegistry $registry): void
    {
        $webhooks = Webhook::query()->get(['name', 'is_active', 'last_fired_at']);

        $whActive = $registry->registerGauge('zepeed', 'webhook_active', '1 if the webhook is active', ['name']);
        $whTs = $registry->registerGauge('zepeed', 'webhook_last_fired_timestamp', 'Unix epoch of last dispatch, 0 if never', ['name']);
        $whTotal = $registry->registerGauge('zepeed', 'webhook_deliveries_total', 'Delivery count in the last 24 hours by result', ['name', 'result']);
        $whRate = $registry->registerGauge('zepeed', 'webhook_success_rate_percent', 'Delivery success % in the last 24 hours', ['name']);
        $whDur = $registry->registerGauge('zepeed', 'webhook_avg_duration_ms', 'Average successful delivery duration in ms (last 24h)', ['name']);

        foreach ($webhooks as $wh) {
            $whActive->set((bool) $wh->is_active ? 1.0 : 0.0, [(string) $wh->name]);
            $whTs->set($wh->last_fired_at?->getTimestamp() ?? 0.0, [(string) $wh->name]);
        }

        $deliveries = WebhookDelivery::query()
            ->with('webhook:id,name')
            ->where('created_at', '>=', now()->subHours(24))
            ->get(['id', 'webhook_id', 'success', 'duration_ms']);

        /** @var array<string, array<string, int|float>> $byWebhook */
        $byWebhook = [];

        foreach ($deliveries->groupBy(static fn (WebhookDelivery $d) => $d->webhook_id . '|' . ($d->success ? '1' : '0')) as $key => $group) {
            [, $successFlag] = explode('|', (string) $key, 2);
            $name = (string) $group->first()->webhook->name;
            $cnt = $group->count();
            $result = $successFlag === '1' ? 'success' : 'failed';

            $byWebhook[$name][$result] = $cnt;
            $whTotal->set((float) $cnt, [$name, $result]);

            if ($successFlag === '1') {
                $avgDur = $group->avg('duration_ms');

                if ($avgDur !== null) {
                    $whDur->set(round((float) $avgDur, 2), [$name]);
                }
            }
        }

        foreach ($byWebhook as $name => $counts) {
            $success = (int) ($counts['success'] ?? 0);
            $failed = (int) ($counts['failed'] ?? 0);
            $tot = $success + $failed;
            $whRate->set($tot > 0 ? round($success / $tot * 100, 2) : 0.0, [$name]);
        }
    }

    /**
     * Group 8 — Static app info gauge.
     */
    private function registerAppInfo(CollectorRegistry $registry): void
    {
        $info = $registry->registerGauge('zepeed', 'info', 'Static application metadata', ['version', 'php_version']);

        $info->set(1.0, [
            (string) config('app.version', '1.0.0'),
            PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION,
        ]);
    }
}
