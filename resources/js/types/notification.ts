export type NotificationType =
    | "ExportCompletedNotification"
    | "ExportFailedNotification";

export interface ExportCompletedData {
    export_id: string;
    module: string;
    module_label: string;
    format: string;
    row_count: number | null;
    download_url: string;
    expires_at: string | null;
}

export interface ExportFailedData {
    export_id: string;
    module_label: string;
    failure_message: string | null;
}

export interface TUserNotification {
    id: string;
    type: NotificationType;
    data: ExportCompletedData | ExportFailedData;
    read_at: string | null;
    created_at: string;
}

export type TNotification =
    | "success"
    | "error"
    | "warning"
    | "info"
    | (string & {});

export interface INotification {
    type: TNotification;
    title?: string;
    message: string;
}
