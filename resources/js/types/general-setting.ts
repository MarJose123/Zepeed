export interface TSchedulerJob {
    name: string;
    description: string;
    last_run: string;
    status: "healthy" | "pending" | "idle";
}

export interface TStorageTable {
    name: string;
    size_mb: number;
    size_display: string; // "284 MB" | "420 KB" | "< 1 KB"
    row_count: number;
    empty: boolean; // true when InnoDB allocated space but table has 0 rows
    percentage: number;
}

export interface TDowntimeLog {
    event: "DOWN" | "UP";
    triggered_by: string;
    duration: string | null;
    timestamp: string;
}

export interface TDowntimeLogPaginator {
    data: TDowntimeLog[];
    current_page: number;
    last_page: number;
    total: number;
}

export interface TRetentionProjection {
    table: string;
    current_rows: number;
    max_rows: number;
    window_days: number;
}

export interface TGeneralStats {
    total_results: number;
    results_this_week: number;
    db_size_mb: number;
    db_name: string;
    uptime_percent: number;
    queue_workers_running: number;
    queue_workers_total: number;
}

export interface TGeneralSettings {
    app_url: string;
    app_env: "production" | "local" | "staging";
    timezone: string;
    maintenance_enabled: boolean;
    bypass_secret: string;
    retry_after_value: number;
    retry_after_unit: "seconds" | "minutes";
    maintenance_redirect: string;
    result_auto_purge: boolean;
    result_retention_days: number;
    exempt_failed: boolean;
    webhook_retention_days: number;
    webhook_extended_retention: boolean;
}

export interface TGeneralSettingsPageProps {
    settings: TGeneralSettings;
    stats: TGeneralStats;
    scheduler_jobs: TSchedulerJob[];
    storage_tables: TStorageTable[];
    downtime_logs: TDowntimeLogPaginator;
    retention_projections: TRetentionProjection[];
    timezones: string[];
}

export type TRetryAfterUnit = "seconds" | "minutes";

export type TDangerAction =
    | "clear_results"
    | "clear_log"
    | "reset_config"
    | "factory_reset";

export interface TDangerActionConfig {
    key: TDangerAction;
    title: string;
    desc: string;
    word: string;
    label: string;
    detail: string;
}

export type TJobStatus = TSchedulerJob["status"];
export type TDowntimeEvent = TDowntimeLog["event"];
export type TCacheKey = "optimize" | "optimize:clear";
