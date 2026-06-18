export interface ApiToken {
    id: number;
    name: string;
    last_used_at: string | null;
    last_used_ip: string | null;
    last_used_agent: string | null;
    expires_at: string | null;
    is_expired: boolean;
    created_at: string;
}
