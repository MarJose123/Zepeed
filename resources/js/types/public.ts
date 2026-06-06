export interface PublicStats {
    total_tests: number
    avg_download: number
    avg_upload: number
    avg_ping: number
    provider_count: number
}

export interface TrendPoint {
    label: string
    download: number
    upload: number
    ping: number
}

export interface PublicSpeedResult {
    id: string
    provider_name: string
    download_mbps: number | null
    upload_mbps: number | null
    ping_ms: number | null
    jitter_ms: number | null
    measured_at: string
}

export interface PublicAlertItem {
    id: string
    name: string
    is_enabled: boolean
    updated_at: string | null
}
