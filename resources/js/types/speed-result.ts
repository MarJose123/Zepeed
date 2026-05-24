export interface TSpeedResultProvider {
    id: string;
    name: string;
}

export interface TSpeedResult {
    id: string;
    provider_slug: string;
    provider_name: string;
    status: string;
    download: number | null;
    upload: number | null;
    ping: number | null;
    jitter: number | null;
    server_name: string | null;
    server_location: string | null;
    isp: string | null;
    share_url: string | null;
    measured_at: string;
}

export interface TSpeedResultStats {
    average: number | null;
    peak: number | null;
    best: number | null;
    worst: number | null;
    lowest: number | null;
    threshold_count: number;
    threshold_label: string;
    threshold_pct: number;
    total: number;
}

export interface TSpeedResultFilters {
    provider: string | null;
    month: string | null;
    per_page: number;
}

export interface TProviderOption {
    slug: string;
    name: string;
}
