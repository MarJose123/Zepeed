export interface Webhook {
    id: string;
    name: string;
    url: string;
    url_preview: string;
    method: "POST" | "GET" | "PUT" | "PATCH";
    has_secret: boolean;
    headers: Array<{ key: string; value: string }>;
    timeout: number;
    retry_attempts: number;
    verify_ssl: boolean;
    is_active: boolean;
    last_fired_at: string | null;
    is_used_in_rules: boolean;
    used_in_rule_names: string[];
    created_at: string;
}

export interface WebhookDelivery {
    id: string;
    event: string;
    status_code: number | null;
    status_text: string | null;
    duration_ms: number | null;
    attempt: number;
    max_attempts: number;
    success: boolean;
    created_at: string;
}
