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
