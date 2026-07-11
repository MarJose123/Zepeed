export type ExportFormat = "csv" | "xlsx" | "json";
export type ExportModule =
    "speed_download" | "speed_upload" | "speed_latency" | "ping_result";
export type ExportStatus = "pending" | "processing" | "completed" | "failed";

export interface TExportRequest {
    id: string;
    module: ExportModule;
    format: ExportFormat;
    status: ExportStatus;
    row_count: number | null;
    expires_at: string | null;
    created_at: string;
}

export interface ExportCompletedPayload {
    export_id: string;
    module: ExportModule;
    module_label: string;
    format: ExportFormat;
    row_count: number | null;
    download_url: string;
}

export interface ExportFailedPayload {
    export_id: string;
    module_label: string;
    failure_message: string | null;
}
