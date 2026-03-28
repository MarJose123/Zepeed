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

export interface ChartDataset {
    download: number[];
    upload: number[];
}

export interface ChartRangeData {
    labels: string[];
    datasets: Record<string, ChartDataset>;
}

export interface ChartData {
    "24h": ChartRangeData;
    "7d": ChartRangeData;
    "30d": ChartRangeData;
}
