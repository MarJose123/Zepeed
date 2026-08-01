export interface ApiToken {
    id: number;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    last_used_ip: string | null;
    browser: string | null;
    platform: string | null;
    expires_at: string | null;
    is_expired: boolean;
    created_at: string;
}

export interface TokenAbilityOption {
    value: string;
    kind: string;
    label: string;
}

export interface TokenAbilityGroup {
    module: string;
    abilities: TokenAbilityOption[];
}
