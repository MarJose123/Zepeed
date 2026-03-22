import type { Appearance } from "@/types/ui";

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    unread_count: number;
    notifications: DbNotification[];
    [key: string]: unknown;
};

export type UserAppearance = {
    mode: Appearance;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export interface DbNotification {
    id: string;
    type: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
}

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};

export type WebSession = {
    agent: {
        browser: string | null;
        platform: string | null;
        is_desktop: boolean;
        country: string | null;
        country_code: string | null;
        flag: string | null;
        city: string | null;
        timezone: string | null;
        latitude: string | null;
        longitude: string | null;
    };
    session_id: string;
    ip_address: string;
    is_current_device: boolean;
    last_active: string;
    risk: number;
};
