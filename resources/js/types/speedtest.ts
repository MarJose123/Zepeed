export interface SpeedtestEventPayload {
    provider_slug: string;
    provider_name: string;
    last_run_at: string | null;
    last_run_status: string | null;
    status_badge: string;
    is_healthy: boolean;
    is_runnable: boolean;
}

export interface SpeedtestCompletedPayload extends SpeedtestEventPayload {
    download_mbps: number;
    upload_mbps: number;
    ping_ms: number;
    jitter_ms: number | null;
    server_name: string | null;
    server_location: string | null;
    isp: string | null;
    measured_at: string;
}

export interface SpeedtestExceptionPayload extends SpeedtestEventPayload {
    reason: string;
    message: string;
}

export interface SpeedtestSkippedPayload extends SpeedtestEventPayload {
    reason: string;
}
