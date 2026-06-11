import { useEchoPublic } from "@laravel/echo-vue";
import type { PublicDashboardRefreshPayload } from "@/types/public";

/**
 * Listens on the public channel `public.dashboard` for `dashboard.refresh`
 * events so guests on the public dashboard see live updates without polling.
 * Public channel requires no authentication.
 */
export function usePublicDashboardRefresh(
    onRefresh: (payload: PublicDashboardRefreshPayload) => void,
): void {
    useEchoPublic<PublicDashboardRefreshPayload>(
        "public.dashboard",
        ".dashboard.refresh",
        (payload) => onRefresh(payload),
    );
}
