<?php

namespace App\Services\Prometheus;

use Illuminate\Support\Facades\DB;
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
     * min_ms, max_ms, stddev_ms, packets_* require a join to ping_results.
     */
    private function registerPingLatest(CollectorRegistry $registry): void
    {
        $rows = DB::select(
            'SELECT pt.label, pt.host, pt.status,
                    pt.last_avg_ms, pt.last_loss_percent,
                    UNIX_TIMESTAMP(pt.last_tested_at) AS tested_ts,
                    pr.min_ms, pr.max_ms, pr.stddev_ms,
                    pr.packets_sent, pr.packets_received
             FROM ping_targets pt
             LEFT JOIN ping_results pr ON pr.id = (
                 SELECT id FROM ping_results
                 WHERE ping_target_id = pt.id
                 ORDER BY measured_at DESC LIMIT 1
             )
             WHERE pt.is_enabled = 1',
        );

        $up = $registry->registerGauge('zepeed', 'ping_up', '1 if PingStatus is ok, 0 otherwise', ['target', 'host']);
        $avg = $registry->registerGauge('zepeed', 'ping_avg_ms', 'Average round-trip in ms', ['target', 'host']);
        $min = $registry->registerGauge('zepeed', 'ping_min_ms', 'Minimum round-trip in ms', ['target', 'host']);
        $max = $registry->registerGauge('zepeed', 'ping_max_ms', 'Maximum round-trip in ms', ['target', 'host']);
        $std = $registry->registerGauge('zepeed', 'ping_stddev_ms', 'Std deviation of round-trip in ms', ['target', 'host']);
        $pls = $registry->registerGauge('zepeed', 'ping_packet_loss_percent', 'Packet loss %', ['target', 'host']);
        $snt = $registry->registerGauge('zepeed', 'ping_packets_sent', 'Packets sent in last probe', ['target', 'host']);
        $rcv = $registry->registerGauge('zepeed', 'ping_packets_received', 'Packets received in last probe', ['target', 'host']);
        $ts = $registry->registerGauge('zepeed', 'ping_last_tested_timestamp', 'Unix epoch of last probe, 0 if never', ['target', 'host']);

        foreach ($rows as $row) {
            $lbl = [(string) $row->label, (string) $row->host];

            $up->set((string) $row->status === 'ok' ? 1.0 : 0.0, $lbl);
            $ts->set($row->tested_ts !== null ? (float) $row->tested_ts : 0.0, $lbl);

            if ($row->last_avg_ms !== null) {
                $avg->set((float) $row->last_avg_ms, $lbl);
            }
            if ($row->last_loss_percent !== null) {
                $pls->set((float) $row->last_loss_percent, $lbl);
            }
            if ($row->min_ms !== null) {
                $min->set((float) $row->min_ms, $lbl);
            }
            if ($row->max_ms !== null) {
                $max->set((float) $row->max_ms, $lbl);
            }
            if ($row->stddev_ms !== null) {
                $std->set((float) $row->stddev_ms, $lbl);
            }
            if ($row->packets_sent !== null) {
                $snt->set((float) $row->packets_sent, $lbl);
            }
            if ($row->packets_received !== null) {
                $rcv->set((float) $row->packets_received, $lbl);
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
        $rows = DB::select(
            'SELECT pt.label, pt.host, pr.status, COUNT(*) AS cnt
             FROM ping_results pr
             JOIN ping_targets pt ON pt.id = pr.ping_target_id
             WHERE pr.measured_at >= NOW() - INTERVAL 24 HOUR
               AND pt.is_enabled = 1
             GROUP BY pr.ping_target_id, pt.label, pt.host, pr.status',
        );

        $total = $registry->registerGauge('zepeed', 'ping_results_total', 'Result count in the last 24 hours by status', ['target', 'host', 'status']);
        $rate = $registry->registerGauge('zepeed', 'ping_success_rate_percent', '(success+partial)/total*100 in last 24h', ['target', 'host']);

        /** @var array<string, array<string, mixed>> $byTarget */
        $byTarget = [];

        foreach ($rows as $row) {
            $key = (string) $row->label . '||' . (string) $row->host;
            $status = (string) $row->status;
            $cnt = (int) $row->cnt;

            $byTarget[$key] ??= ['label' => (string) $row->label, 'host' => (string) $row->host];
            $byTarget[$key][$status] = $cnt;

            $total->set((float) $cnt, [(string) $row->label, (string) $row->host, $status]);
        }

        foreach ($byTarget as $data) {
            $success = ((int) ($data['success'] ?? 0)) + ((int) ($data['partial'] ?? 0));
            $failed = (int) ($data['failed'] ?? 0);
            $tot = $success + $failed;
            $rate->set($tot > 0 ? round($success / $tot * 100, 2) : 0.0, [(string) $data['label'], (string) $data['host']]);
        }
    }
}
