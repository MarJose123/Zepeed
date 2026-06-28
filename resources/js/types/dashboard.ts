export interface SparklinePoint {
    download_mbps: number;
    upload_mbps: number;
    measured_at: string;
}

export interface ProviderLatest {
    download_mbps: number;
    upload_mbps: number;
    ping_ms: number;
    jitter_ms: number | null;
}

export interface ProviderCard {
    slug: string;
    name: string;
    website_link: string;
    status_badge: "success" | "danger" | "warning" | "neutral";
    last_run_at: string | null;
    last_run_status: "success" | "failed" | "skipped" | null;
    is_enabled: boolean;
    next_run_at: string | null;
    latest: ProviderLatest | null;
    sparkline: SparklinePoint[];
}

export interface DashboardRefreshPayload {
    provider_slug: string;
}

export interface RecentPingResult {
    id: string;
    target_label: string;
    target_host: string;
    status: "success" | "partial" | "failed";
    avg_ms: number | null;
    packet_loss_percent: number;
    measured_at: string;
}

// ── Dashboard chart types ─────────────────────────────────────────────────────

/** Range preset or custom date window. */
export type DashboardChartRange = "1d" | "7d" | "30d" | "custom";

/** Provider that has data in the current range window. */
export interface DashboardProviderInfo {
    slug: string;
    label: string;
}

/**
 * One result row, pivoted by provider.
 * `label` drives the XAxis tick; remaining keys follow "{slug}_dl" / "{slug}_ul".
 */
export interface DashboardSeriesPoint {
    [key: string]: number | string | null;
    label: string;
}

/** Configuration for a single chart line. */
export interface DashboardSeriesConfig {
    key: string;
    label: string;
    color: string;
    unit: string;
    dashed?: boolean;
}
