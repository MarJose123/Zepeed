export type PingStatus = "pending" | "ok" | "warn" | "failed";
export type PingResultStatus = "success" | "partial" | "failed";

export interface PingTarget {
    id: string;
    label: string;
    host: string;
    is_enabled: boolean;
    packets: number;
    timeout_seconds: number;
    status: PingStatus;
    status_label: string;
    last_avg_ms: number | null;
    last_loss_percent: number | null;
    last_tested_at: string | null;
    created_at: string;
}

export interface PingResult {
    id: string;
    ping_target_id: string;
    target_label: string | null;
    target_host: string | null;
    status: PingResultStatus;
    status_label: string;
    packets_sent: number;
    packets_received: number;
    packet_loss_percent: number;
    min_ms: number | null;
    avg_ms: number | null;
    max_ms: number | null;
    stddev_ms: number | null;
    raw_output: string | null;
    failure_reason: string | null;
    measured_at: string;
}

export interface PingTrendBucket {
    label: string;
    // dynamic keys: ping_target_id → { avg_ms, loss }
    [targetId: string]: string | { avg_ms: number | null; loss: number | null };
}

export interface PingResultStats {
    total_tests: number;
    avg_latency_ms: number | null;
    avg_packet_loss: number | null;
}

export interface PingResultFilters {
    range: "24h" | "7d" | "30d";
    target: string | null;
    status: PingResultStatus | null;
    per_page: number;
}

export interface PingResultPagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface PingSeriesConfig {
    key: string;
    label: string;
    color: string;
}
