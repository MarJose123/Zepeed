export interface Apprise {
    id: string;
    name: string;
    url: string;
    url_preview: string;
    tags: string[];
    has_credentials: boolean;
    username: string | null;
    timeout: number;
    verify_ssl: boolean;
    is_active: boolean;
    last_fired_at: string | null;
    is_used_in_rules: boolean;
    used_in_rule_names: string[];
    created_at: string;
}
