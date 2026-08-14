export type WorkflowRuleEvent =
    "run_completes" | "run_fails" | "run_skipped" | "any" | "ping";

export type WorkflowRuleMetric =
    | "status"
    | "download_mbps"
    | "upload_mbps"
    | "ping_ms"
    | "jitter_ms"
    | "packet_loss"
    | "latency_avg"
    | "latency_max"
    | "consecutive_failures";

export type WorkflowRuleOperator =
    | "is"
    | "is_not"
    | "is_above"
    | "is_below"
    | "is_above_or_equal"
    | "is_below_or_equal";

export interface WorkflowRuleCondition {
    id?: string;
    metric: WorkflowRuleMetric;
    metric_label?: string;
    metric_unit?: string;
    operator: WorkflowRuleOperator;
    operator_label?: string;
    value: string;
    lookback_minutes?: number | null;
    sort_order: number;
}

export interface WorkflowRuleAction {
    id?: string;
    type: "email" | "webhook" | "apprise";
    mail_provider_id: string | null;
    mail_provider_label?: string | null;
    email_template_id: string | null;
    email_template_label?: string | null;
    recipient_email: string | null;
    webhook_id: string | null;
    webhook_label?: string | null;
    apprise_id: string | null;
    apprise_label?: string | null;
    sort_order: number;
}

export interface WorkflowRule {
    id: string;
    name: string;
    provider_slug: string | null;
    ping_target_id: string | null;
    target_label?: string | null;
    target_host?: string | null;
    event: WorkflowRuleEvent;
    event_label: string;
    condition_operator: "and" | "or";
    is_active: boolean;
    cooldown_minutes: number;
    last_triggered_at: string | null;
    conditions: WorkflowRuleCondition[];
    actions: WorkflowRuleAction[];
    created_at: string;
}

export const EVENT_OPTIONS = [
    { value: "run_completes", label: "Speedtest run completes" },
    { value: "run_fails", label: "Speedtest run fails" },
    { value: "run_skipped", label: "Speedtest run is skipped" },
    { value: "any", label: "Any speedtest event" },
    { value: "ping", label: "Ping result recorded" },
] as const;

export const METRIC_OPTIONS = [
    { value: "status", label: "Status", numeric: false },
    { value: "download_mbps", label: "Download Mbps", numeric: true },
    { value: "upload_mbps", label: "Upload Mbps", numeric: true },
    { value: "ping_ms", label: "Ping ms", numeric: true },
    { value: "jitter_ms", label: "Jitter ms", numeric: true },
    { value: "packet_loss", label: "Packet loss %", numeric: true },
    { value: "latency_avg", label: "Latency (avg)", numeric: true },
    { value: "latency_max", label: "Latency (max)", numeric: true },
    {
        value: "consecutive_failures",
        label: "Consecutive Failures",
        numeric: true,
    },
] as const;

export const OPERATOR_OPTIONS = [
    { value: "is", label: "is", numericOnly: false },
    { value: "is_not", label: "is not", numericOnly: false },
    { value: "is_above", label: "is above", numericOnly: true },
    { value: "is_below", label: "is below", numericOnly: true },
    {
        value: "is_above_or_equal",
        label: "is above or equal",
        numericOnly: true,
    },
    {
        value: "is_below_or_equal",
        label: "is below or equal",
        numericOnly: true,
    },
] as const;

export const STATUS_VALUES = [
    { value: "failed", label: "failed" },
    { value: "success", label: "success" },
    { value: "skipped", label: "skipped" },
] as const;

export const PING_METRIC_VALUES = [
    "latency_avg",
    "latency_max",
    "packet_loss",
    "consecutive_failures",
] as const;

export const isPingMetric = (metric: string): boolean =>
    (PING_METRIC_VALUES as readonly string[]).includes(metric);

export const metricUnit = (metric: string): string => {
    switch (metric) {
        case "ping_ms":
        case "jitter_ms":
        case "latency_avg":
        case "latency_max":
            return "ms";
        case "packet_loss":
            return "%";
        case "consecutive_failures":
            return "failures";
        default:
            return "Mbps";
    }
};

export const PING_LOOKBACK_OPTIONS = [
    { value: 1, label: "1 min" },
    { value: 5, label: "5 min" },
    { value: 10, label: "10 min" },
    { value: 15, label: "15 min" },
    { value: 30, label: "30 min" },
    { value: 60, label: "1 hour" },
    { value: 120, label: "2 hours" },
] as const;
