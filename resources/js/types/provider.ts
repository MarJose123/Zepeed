export interface Provider {
    id: number;
    slug: "speedtest" | "librespeed" | "fastcom";
    name: string;
    website_link: string;
    requires_server_url: boolean;
    requires_chromium: boolean;
    is_enabled: boolean;
    is_runnable: boolean;
    is_healthy: boolean;
    alert_on_failure: boolean;
    server_url: string | null;
    server_id: string | null;
    extra_flags: string | null;
    last_run_at: string | null;
    last_run_status: "success" | "failed" | "skipped" | null;
    status_badge: "success" | "danger" | "warning" | "neutral";
}

export interface ProviderSchedule {
    id: string;
    provider_slug: "speedtest" | "librespeed" | "fastcom";
    provider_name: string;
    cron_expression: string | null;
    is_enabled: boolean;
    next_run_at: string | null;
    last_scheduled_at: string | null;
}

export interface MaintenanceWindow {
    id: string;
    label: string;
    type: "indefinite" | "one_time" | "recurring";
    type_label: string;
    is_active: boolean;
    providers: string[];
    covers_all: boolean;
    starts_at: string | null;
    ends_at: string | null;
    cron_expression: string | null;
    duration_minutes: number | null;
    notes: string | null;
    is_currently_active: boolean;
    created_at: string;
}
