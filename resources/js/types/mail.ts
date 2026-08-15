export type MailDriver =
    "smtp" | "resend" | "mailgun" | "postmark" | "ses" | "sendmail";

/** Sentinel returned by the API for stored secrets; kept on save when unchanged. */
export const SECRET_MASK = "********";

export interface MailProvider {
    id: string;
    driver: MailDriver;
    driver_label: string;
    driver_description: string;
    label: string;
    priority: number;
    is_active: boolean;
    from_address: string;
    from_name: string;
    last_used_at: string | null;
    last_failed_at: string | null;
    failure_count: number;
    is_primary: boolean;
    /**
     * Editable driver config. Secret values are masked with {@link SECRET_MASK}
     * and preserved server-side when saved unchanged.
     */
    config: Record<string, string>;
    config_summary: string;
    created_at: string;
}

export interface MailDriverOption {
    value: MailDriver;
    label: string;
    description: string;
}

export const MAIL_DRIVERS: MailDriverOption[] = [
    { value: "smtp", label: "SMTP", description: "Custom mail server" },
    { value: "resend", label: "Resend", description: "Modern API-based" },
    { value: "mailgun", label: "Mailgun", description: "Scalable API" },
    {
        value: "postmark",
        label: "Postmark",
        description: "High deliverability",
    },
    { value: "ses", label: "Amazon SES", description: "AWS cloud mail" },
    { value: "sendmail", label: "Sendmail", description: "Server binary" },
];
