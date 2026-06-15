<?php

namespace App\Http\Controllers;

use App\Models\AlertRule;
use App\Models\PingResult;
use App\Models\Provider;
use App\Models\SpeedResult;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PublicDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $stats = [
            'total_tests'    => SpeedResult::query()->count(),
            'avg_download'   => round((float) SpeedResult::query()
                ->where('measured_at', '>=', now()->subHours(24))
                ->avg('download_mbps'), 1),
            'avg_upload'     => round((float) SpeedResult::query()
                ->where('measured_at', '>=', now()->subHours(24))
                ->avg('upload_mbps'), 1),
            'avg_ping'       => round((float) SpeedResult::query()
                ->where('measured_at', '>=', now()->subHours(24))
                ->avg('ping_ms'), 1),
            'provider_count' => Provider::query()->count(),
        ];

        /** @var array<int, array{label: string, download: float, upload: float, ping: float}> $trend */
        $trend = DB::table('speed_results')
            ->select([
                DB::raw('DATE_FORMAT(measured_at, "%Y-%m-%d %H:00:00") as hour_bucket'),
                DB::raw('ROUND(AVG(download_mbps), 1) as download'),
                DB::raw('ROUND(AVG(upload_mbps), 1) as upload'),
                DB::raw('ROUND(AVG(ping_ms), 1) as ping'),
            ])
            ->where('measured_at', '>=', now()->subHours(24))
            ->groupBy('hour_bucket')
            ->orderBy('hour_bucket')
            ->get()
            ->map(static fn (object $row): array => [
                'label'    => (string) $row->hour_bucket,
                'download' => (float) $row->download,
                'upload'   => (float) $row->upload,
                'ping'     => (float) $row->ping,
            ])
            ->values()
            ->all();

        /** @var array<int, array{id: string, provider_name: string, download_mbps: float|null, upload_mbps: float|null, ping_ms: float|null, jitter_ms: float|null, measured_at: string}> $recentResults */
        $recentResults = DB::table('speed_results')
            ->select([
                'speed_results.id',
                'speed_results.download_mbps',
                'speed_results.upload_mbps',
                'speed_results.ping_ms',
                'speed_results.jitter_ms',
                'speed_results.measured_at',
                DB::raw('COALESCE(providers.name, speed_results.provider_slug) as provider_name'),
            ])
            ->leftJoin('providers', 'providers.slug', '=', 'speed_results.provider_slug')
            ->latest('speed_results.measured_at')
            ->limit(10)
            ->get()
            ->map(static fn (object $row): array => [
                'id'            => (string) $row->id,
                'provider_name' => (string) $row->provider_name,
                'download_mbps' => $row->download_mbps !== null ? (float) $row->download_mbps : null,
                'upload_mbps'   => $row->upload_mbps !== null ? (float) $row->upload_mbps : null,
                'ping_ms'       => $row->ping_ms !== null ? (float) $row->ping_ms : null,
                'jitter_ms'     => $row->jitter_ms !== null ? (float) $row->jitter_ms : null,
                'measured_at'   => (string) $row->measured_at,
            ])
            ->values()
            ->all();

        /** @var array<int, array<string, mixed>> $recentPingResults */
        $recentPingResults = PingResult::query()
            ->with('target')
            ->latest('measured_at')
            ->limit(10)
            ->get()
            ->map(static fn (PingResult $r): array => [
                'id'                  => $r->id,
                'target_label'        => $r->target->label,
                'target_host'         => $r->target->host,
                'status'              => $r->status->value,
                'avg_ms'              => $r->avg_ms,
                'packet_loss_percent' => $r->packet_loss_percent,
                'measured_at'         => $r->measured_at->toIso8601String(),
            ])
            ->values()
            ->all();

        $alertHistory = AlertRule::query()
            ->select(['id', 'name', 'is_active', 'updated_at'])
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(static fn (AlertRule $rule): array => [
                'id'         => $rule->id,
                'name'       => $rule->name,
                'is_enabled' => $rule->is_active,
                'updated_at' => $rule->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('public/Dashboard', [
            'stats'             => $stats,
            'trend'             => $trend,
            'recentResults'     => $recentResults,
            'recentPingResults' => $recentPingResults,
            'alertHistory'      => $alertHistory,
        ]);
    }
}
