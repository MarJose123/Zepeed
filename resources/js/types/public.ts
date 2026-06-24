// ── Existing public dashboard types ──────────────────────────────────────────

export interface PublicStats {
    total_tests: number;
    avg_download: number;
    avg_upload: number;
    avg_ping: number;
    provider_count: number;
}

export interface TrendPoint {
    label: string;
    download: number;
    upload: number;
    ping: number;
}

export interface PublicSpeedResult {
    id: string;
    provider_name: string;
    download_mbps: number | null;
    upload_mbps: number | null;
    ping_ms: number | null;
    jitter_ms: number | null;
    measured_at: string;
}

export interface PublicAlertItem {
    id: string;
    name: string;
    is_enabled: boolean;
    updated_at: string | null;
}

export interface PublicDashboardRefreshPayload {
    result: {
        provider_slug: string;
        provider_name: string;
        download_mbps: number;
        upload_mbps: number;
        ping_ms: number;
        jitter_ms: number | null;
        measured_at: string;
    };
}

export interface PublicPingResult {
    id: string;
    target_label: string;
    target_host: string;
    status: "success" | "partial" | "failed";
    avg_ms: number | null;
    packet_loss_percent: number;
    measured_at: string;
}

export interface PublicPingRefreshPayload {
    result: {
        target_id: string;
        target_label: string;
        target_host: string;
        status: string;
        avg_ms: number | null;
        packet_loss_percent: number;
        measured_at: string;
    };
}

// ── Metrics Dashboard ─────────────────────────────────────────────────────────

export type MetricsRange = "1d" | "7d" | "30d" | "custom";

export type MetricsGranularity = "hourly" | "daily" | "weekly";

export interface SpeedSeriesPoint {
    label: string;
    download: number;
    upload: number;
    ping: number;
}

export interface LatencySeriesPoint {
    label: string;
    ping: number;
    download_latency: number;
    upload_latency: number;
}

export interface JitterSeriesPoint {
    label: string;
    download_jitter: number;
    upload_jitter: number;
    ping_jitter: number;
}

export interface MetricStat {
    latest: number;
    average: number;
    p95: number;
    maximum: number;
    minimum: number;
}

export interface SpeedStats {
    download: MetricStat;
    upload: MetricStat;
}

export interface LatencyStats {
    ping: MetricStat;
    download_latency: MetricStat;
    upload_latency: MetricStat;
}

export interface JitterStats {
    download_jitter: MetricStat;
    upload_jitter: MetricStat;
    ping_jitter: MetricStat;
}

export interface MetricsRefreshPayload {
    result: {
        provider_slug: string;
        provider_name: string;
        download_mbps: number;
        upload_mbps: number;
        ping_ms: number;
        jitter_ms: number | null;
        measured_at: string;
    };
}
