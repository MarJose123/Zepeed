<?php

namespace App\Services\Prometheus;

use App\Enums\PingStatus;
use App\Models\PingResult;
use App\Models\PingTarget;
use Illuminate\Contracts\Database\Query\Builder;
use Prometheus\CollectorRegistry;

class PingMetricsBuilderService
{
    /**
     * Register Groups 4 and 5 — ping target gauges and 24h health.
     */
    public function register(CollectorRegistry $registry): void
    {
        $this->registerPingLatest($registry);
        $this->registerPingHealth($registry);
    }

    /**
     * Group 4 — Latest result per enabled ping target.
     *
     * ping_targets.last_avg_ms and last_loss_percent are denormalized.
     * min_ms, max_ms, stddev_ms, packets_* come from the latestResult
     * relation (Eloquent's latestOfMany(), a single efficient query).
     */
    private function registerPingLatest(CollectorRegistry $registry): void
    {
        $targets = PingTarget::query()
            ->where('is_enabled', true)
            ->with('latestResult')
            ->get();

        $up = $registry->registerGauge('zepeed', 'ping_up', '1 if PingStatus is ok, 0 otherwise', ['target', 'host']);
        $avg = $registry->registerGauge('zepeed', 'ping_avg_ms', 'Average round-trip in ms', ['target', 'host']);
        $min = $registry->registerGauge('zepeed', 'ping_min_ms', 'Minimum round-trip in ms', ['target', 'host']);
        $max = $registry->registerGauge('zepeed', 'ping_max_ms', 'Maximum round-trip in ms', ['target', 'host']);
        $std = $registry->registerGauge('zepeed', 'ping_stddev_ms', 'Std deviation of round-trip in ms', ['target', 'host']);
        $pls = $registry->registerGauge('zepeed', 'ping_packet_loss_percent', 'Packet loss %', ['target', 'host']);
        $snt = $registry->registerGauge('zepeed', 'ping_packets_sent', 'Packets sent in last probe', ['target', 'host']);
        $rcv = $registry->registerGauge('zepeed', 'ping_packets_received', 'Packets received in last probe', ['target', 'host']);
        $ts = $registry->registerGauge('zepeed', 'ping_last_tested_timestamp', 'Unix epoch of last probe, 0 if never', ['target', 'host']);

        foreach ($targets as $target) {
            $lbl = [(string) $target->label, (string) $target->host];

            $up->set($target->status === PingStatus::Ok ? 1.0 : 0.0, $lbl);
            $ts->set($target->last_tested_at !== null ? (float) $target->last_tested_at->getTimestamp() : 0.0, $lbl);

            if ($target->last_avg_ms !== null) {
                $avg->set((float) $target->last_avg_ms, $lbl);
            }
            if ($target->last_loss_percent !== null) {
                $pls->set((float) $target->last_loss_percent, $lbl);
            }

            $latest = $target->latestResult;

            if ($latest?->min_ms !== null) {
                $min->set((float) $latest->min_ms, $lbl);
            }
            if ($latest?->max_ms !== null) {
                $max->set((float) $latest->max_ms, $lbl);
            }
            if ($latest?->stddev_ms !== null) {
                $std->set((float) $latest->stddev_ms, $lbl);
            }
            if ($latest?->packets_sent !== null) {
                $snt->set((float) $latest->packets_sent, $lbl);
            }
            if ($latest?->packets_received !== null) {
                $rcv->set((float) $latest->packets_received, $lbl);
            }
        }
    }

    /**
     * Group 5 — 24h ping result counts and success rate per target.
     *
     * partial counts toward success — it means the target is reachable
     * with packet loss, which matches PingTarget::syncFromResult() logic.
     */
    private function registerPingHealth(CollectorRegistry $registry): void
    {
        $rows = PingResult::query()
            ->where('measured_at', '>=', now()->subHours(24))
            ->whereHas('target', static fn (Builder $q) => $q->where('is_enabled', true))
            ->with('target:id,label,host')
            ->get(['id', 'ping_target_id', 'status', 'measured_at']);

        $total = $registry->registerGauge('zepeed', 'ping_results_total', 'Result count in the last 24 hours by status', ['target', 'host', 'status']);
        $rate = $registry->registerGauge('zepeed', 'ping_success_rate_percent', '(success+partial)/total*100 in last 24h', ['target', 'host']);

        /** @var array<string, array<string, mixed>> $byTarget */
        $byTarget = [];

        foreach ($rows->groupBy('ping_target_id') as $targetId => $targetRows) {
            $target = $targetRows->first()->target;
            $label = (string) $target->label;
            $host = (string) $target->host;

            foreach ($targetRows->groupBy(static fn (PingResult $r) => $r->status->value) as $status => $statusRows) {
                $cnt = $statusRows->count();

                $byTarget[(string) $targetId] ??= ['label' => $label, 'host' => $host];
                $byTarget[(string) $targetId][$status] = $cnt;

                $total->set((float) $cnt, [$label, $host, (string) $status]);
            }
        }

        foreach ($byTarget as $data) {
            $success = ((int) ($data['success'] ?? 0)) + ((int) ($data['partial'] ?? 0));
            $failed = (int) ($data['failed'] ?? 0);
            $tot = $success + $failed;
            $rate->set($tot > 0 ? round($success / $tot * 100, 2) : 0.0, [(string) $data['label'], (string) $data['host']]);
        }
    }
}
