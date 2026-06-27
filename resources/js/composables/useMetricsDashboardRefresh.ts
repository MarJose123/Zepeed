import { useEchoPublic } from "@laravel/echo-vue";
import type { MetricsRefreshPayload } from "@/types/public";

export function useMetricsDashboardRefresh(
    onRefresh: (payload: MetricsRefreshPayload) => void,
): void {
    useEchoPublic<MetricsRefreshPayload>(
        "public.metrics",
        ".metrics.refresh",
        (payload) => onRefresh(payload),
    );
}
