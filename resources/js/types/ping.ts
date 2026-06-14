export type PingStatus = "pending" | "success" | "ok" | "warn" | "failed";
export type PingResultStatus = "success" | "partial" | "failed";
export type PingAlertMetric =
    | "latency_avg"
    | "latency_max"
    | "packet_loss"
    | "consecutive_failures";
export type PingAlertOperator =
    | "is_above"
    | "is_below"
    | "is_above_or_equal"
    | "is_below_or_equal"
    | "is"
    | "is_not";

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

export interface PingAlertCondition {
    id?: string;
    metric: PingAlertMetric;
    metric_label?: string;
    metric_unit?: string;
    operator: PingAlertOperator;
    operator_label?: string;
    value: string;
    lookback_minutes: number;
    sort_order: number;
}

export interface PingAlertAction {
    id?: string;
    type: "email" | "webhook";
    mail_provider_id: string | null;
    mail_provider_label?: string | null;
    email_template_id: string | null;
    email_template_label?: string | null;
    recipient_email: string | null;
    webhook_id: string | null;
    webhook_label?: string | null;
    sort_order: number;
}

export interface PingAlertRule {
    id: string;
    name: string;
    ping_target_id: string;
    target_label: string | null;
    target_host: string | null;
    condition_operator: "and" | "or";
    is_active: boolean;
    cooldown_minutes: number;
    last_triggered_at: string | null;
    conditions: PingAlertCondition[];
    actions: PingAlertAction[];
    created_at: string;
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

export const PING_METRIC_OPTIONS = [
    { value: "latency_avg", label: "Latency (avg)", unit: "ms" },
    { value: "latency_max", label: "Latency (max)", unit: "ms" },
    { value: "packet_loss", label: "Packet Loss (%)", unit: "%" },
    { value: "consecutive_failures", label: "Consecutive Failures", unit: "" },
] as const;

export const PING_OPERATOR_OPTIONS = [
    { value: "is_above", label: "is above" },
    { value: "is_below", label: "is below" },
    { value: "is_above_or_equal", label: "is above or equal" },
    { value: "is_below_or_equal", label: "is below or equal" },
    { value: "is", label: "is" },
    { value: "is_not", label: "is not" },
] as const;

export const PING_LOOKBACK_OPTIONS = [
    { value: 1, label: "1 min" },
    { value: 5, label: "5 min" },
    { value: 10, label: "10 min" },
    { value: 15, label: "15 min" },
    { value: 30, label: "30 min" },
    { value: 60, label: "1 hour" },
    { value: 120, label: "2 hours" },
] as const;
