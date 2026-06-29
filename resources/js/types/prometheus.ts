export interface PrometheusConfig {
    id: string;
    is_enabled: boolean;
    allowed_ips: string[];
    cache_ttl: number;
    include_speed: boolean;
    include_ping: boolean;
    include_system: boolean;
    providers: string[];
}

export type PrometheusFormData = Omit<PrometheusConfig, "id">;
