export type AlertRuleEvent =
    | "run_completes"
    | "run_fails"
    | "run_skipped"
    | "any";

export type AlertRuleMetric =
    | "status"
    | "download_mbps"
    | "upload_mbps"
    | "ping_ms"
    | "jitter_ms"
    | "packet_loss";

export type AlertRuleOperator = "is" | "is_not" | "is_above" | "is_below";

export interface AlertRuleCondition {
    id?: string;
    metric: AlertRuleMetric;
    metric_label?: string;
    operator: AlertRuleOperator;
    operator_label?: string;
    value: string;
    sort_order: number;
}

export interface AlertRuleAction {
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

export interface AlertRule {
    id: string;
    name: string;
    provider_slug: string | null;
    event: AlertRuleEvent;
    event_label: string;
    condition_operator: "and" | "or";
    is_active: boolean;
    cooldown_minutes: number;
    last_triggered_at: string | null;
    conditions: AlertRuleCondition[];
    actions: AlertRuleAction[];
    created_at: string;
}

export const EVENT_OPTIONS = [
    { value: "run_completes", label: "Run completes" },
    { value: "run_fails", label: "Run fails" },
    { value: "run_skipped", label: "Run is skipped" },
    { value: "any", label: "Any run event" },
] as const;

export const METRIC_OPTIONS = [
    { value: "status", label: "Status", numeric: false },
    { value: "download_mbps", label: "Download Mbps", numeric: true },
    { value: "upload_mbps", label: "Upload Mbps", numeric: true },
    { value: "ping_ms", label: "Ping ms", numeric: true },
    { value: "jitter_ms", label: "Jitter ms", numeric: true },
    { value: "packet_loss", label: "Packet loss %", numeric: true },
] as const;

export const OPERATOR_OPTIONS = [
    { value: "is", label: "is", numericOnly: false },
    { value: "is_not", label: "is not", numericOnly: false },
    { value: "is_above", label: "is above", numericOnly: true },
    { value: "is_below", label: "is below", numericOnly: true },
] as const;

export const STATUS_VALUES = [
    { value: "failed", label: "failed" },
    { value: "success", label: "success" },
    { value: "skipped", label: "skipped" },
] as const;
