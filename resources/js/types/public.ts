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

/** Speedtest provider active in the selected range. */
export interface ProviderInfo {
    slug: string;
    label: string;
}

/** Ping target active in the selected range. */
export interface PingTargetInfo {
    id: string;
    label: string;
}

/**
 * Chart series configuration passed to MetricsLineChart.
 * Exported so both the page and the component share one definition.
 */
export interface SeriesConfig {
    key: string;
    label: string;
    color: string;
    unit: string;
    /** Render as dashed line — used for upload series on the combined speed chart. */
    dashed?: boolean;
}

/**
 * One time-bucket row from the controller's pivot queries.
 * `label` is the bucket string; every other key is a provider slug or target ID
 * mapped to its metric value (null = no data for that entity in this bucket).
 */
export interface MetricsSeriesPoint {
    [key: string]: number | string | null;
    label: string;
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
