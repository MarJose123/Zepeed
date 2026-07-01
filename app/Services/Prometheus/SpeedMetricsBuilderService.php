<?php

namespace App\Services\Prometheus;

use App\Models\Provider;
use App\Models\SpeedResult;
use Illuminate\Support\Facades\Date;
use Prometheus\CollectorRegistry;

class SpeedMetricsBuilderService
{
    /**
     * Register Groups 1, 2, 3, 10 — speed latest, 24h health,
     * provider state, and failure details.
     *
     * @param array<int,string> $providers
     */
    public function register(CollectorRegistry $registry, array $providers): void
    {
        if (empty($providers)) {
            return;
        }

        $this->registerSpeedLatest($registry, $providers);
        $this->registerSpeedHealth($registry, $providers);
        $this->registerProviderState($registry, $providers);
        $this->registerFailureDetails($registry, $providers);
    }

    /**
     * Group 1 — Latest successful result per provider.
     *
     * @param array<int,string> $providers
     */
    private function registerSpeedLatest(CollectorRegistry $registry, array $providers): void
    {
        $rows = SpeedResult::query()
            ->where('status', 'success')
            ->whereIn('provider_slug', $providers)
            ->toBase()
            ->select(['provider_slug', 'download_mbps', 'upload_mbps', 'ping_ms', 'jitter_ms', 'packet_loss', 'measured_at'])
            ->latest('measured_at')
            ->get()
            ->groupBy('provider_slug')
            ->map(static fn ($group) => $group->first());

        $dl = $registry->registerGauge('zepeed', 'speedtest_download_mbps', 'Latest download speed in Mbps', ['provider']);
        $ul = $registry->registerGauge('zepeed', 'speedtest_upload_mbps', 'Latest upload speed in Mbps', ['provider']);
        $ping = $registry->registerGauge('zepeed', 'speedtest_ping_ms', 'Latest latency to test server in ms', ['provider']);
        $jit = $registry->registerGauge('zepeed', 'speedtest_jitter_ms', 'Latest jitter in ms', ['provider']);
        $loss = $registry->registerGauge('zepeed', 'speedtest_packet_loss_percent', 'Latest packet loss %', ['provider']);
        $ts = $registry->registerGauge('zepeed', 'speedtest_last_result_timestamp', 'Unix epoch of last successful result', ['provider']);

        foreach ($rows as $row) {
            $lbl = [(string) $row->provider_slug];
            $dl->set((float) $row->download_mbps, $lbl);
            $ul->set((float) $row->upload_mbps, $lbl);
            $ping->set((float) $row->ping_ms, $lbl);
            $ts->set((float) Date::parse($row->measured_at)->getTimestamp(), $lbl);

            if ($row->jitter_ms !== null) {
                $jit->set((float) $row->jitter_ms, $lbl);
            }

            if ($row->packet_loss !== null) {
                $loss->set((float) $row->packet_loss, $lbl);
            }
        }
    }

    /**
     * Group 2 — 24h result counts and success rate per provider.
     *
     * @param array<int,string> $providers
     */
    private function registerSpeedHealth(CollectorRegistry $registry, array $providers): void
    {
        $rows = SpeedResult::query()
            ->where('measured_at', '>=', now()->subHours(24))
            ->whereIn('provider_slug', $providers)
            ->toBase()
            ->select(['provider_slug', 'status'])
            ->get();

        $total = $registry->registerGauge('zepeed', 'speedtest_results_total', 'Result count in the last 24 hours by status', ['provider', 'status']);
        $rate = $registry->registerGauge('zepeed', 'speedtest_success_rate_percent', 'Success % in the last 24 hours', ['provider']);

        /** @var array<string, array<string, int>> $byProvider */
        $byProvider = [];

        foreach ($rows->groupBy('provider_slug') as $slug => $providerRows) {
            foreach ($providerRows->groupBy('status') as $status => $statusRows) {
                $cnt = $statusRows->count();
                $byProvider[(string) $slug][(string) $status] = $cnt;
                $total->set((float) $cnt, [(string) $slug, (string) $status]);
            }
        }

        foreach ($byProvider as $slug => $counts) {
            $success = $counts['success'] ?? 0;
            $failed = $counts['failed'] ?? 0;
            $denom = $success + $failed;
            $rate->set($denom > 0 ? round($success / $denom * 100, 2) : 0.0, [$slug]);
        }
    }

    /**
     * Group 3 — Provider enabled/up/last-run state.
     *
     * @param array<int,string> $providers
     */
    private function registerProviderState(CollectorRegistry $registry, array $providers): void
    {
        $rows = Provider::query()
            ->whereIn('slug', $providers)
            ->toBase()
            ->select(['slug', 'is_enabled', 'last_run_status', 'last_run_at'])
            ->get();

        $enabled = $registry->registerGauge('zepeed', 'provider_enabled', '1 if the provider is enabled', ['provider']);
        $up = $registry->registerGauge('zepeed', 'provider_up', '1 if enabled AND last run was successful', ['provider']);
        $runTs = $registry->registerGauge('zepeed', 'provider_last_run_timestamp', 'Unix epoch of last run, 0 if never', ['provider']);

        foreach ($rows as $row) {
            $lbl = [(string) $row->slug];
            $isEnabled = (bool) $row->is_enabled;
            $isUp = $isEnabled && $row->last_run_status === 'success';

            $enabled->set($isEnabled ? 1.0 : 0.0, $lbl);
            $up->set($isUp ? 1.0 : 0.0, $lbl);
            $runTs->set($row->last_run_at !== null ? (float) Date::parse($row->last_run_at)->getTimestamp() : 0.0, $lbl);
        }
    }

    /**
     * Group 10 — 24h failure reason breakdown and last failure per provider.
     *
     * @param array<int,string> $providers
     */
    private function registerFailureDetails(CollectorRegistry $registry, array $providers): void
    {
        $reasonRows = SpeedResult::query()
            ->where('status', 'failed')
            ->where('measured_at', '>=', now()->subHours(24))
            ->whereIn('provider_slug', $providers)
            ->toBase()
            ->select(['provider_slug', 'failure_reason'])
            ->get();

        $byReason = $registry->registerGauge(
            'zepeed',
            'speedtest_failures_by_reason_total',
            'Failed result count in the last 24 hours by reason',
            ['provider', 'reason'],
        );

        foreach ($reasonRows->groupBy(static fn ($r) => $r->provider_slug . '|' . ($r->failure_reason ?? 'unknown')) as $key => $group) {
            [$slug, $reason] = explode('|', (string) $key, 2);
            $byReason->set((float) $group->count(), [$slug, $reason]);
        }

        $lastRows = SpeedResult::query()
            ->where('status', 'failed')
            ->whereIn('provider_slug', $providers)
            ->toBase()
            ->select(['provider_slug', 'failure_reason', 'measured_at'])
            ->latest('measured_at')
            ->get()
            ->groupBy('provider_slug')
            ->map(static fn ($group) => $group->first());

        $lastTs = $registry->registerGauge('zepeed', 'speedtest_last_failure_timestamp', 'Unix epoch of most recent failure, 0 if none', ['provider']);
        $lastInfo = $registry->registerGauge('zepeed', 'speedtest_last_failure_info', 'Info gauge carrying the reason of the most recent failure', ['provider', 'reason']);

        foreach ($providers as $slug) {
            $lastTs->set(0.0, [$slug]);
        }

        foreach ($lastRows as $row) {
            $lastTs->set((float) Date::parse($row->measured_at)->getTimestamp(), [(string) $row->provider_slug]);
            $lastInfo->set(1.0, [(string) $row->provider_slug, (string) ($row->failure_reason ?? 'unknown')]);
        }
    }
}
