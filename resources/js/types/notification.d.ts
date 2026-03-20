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
