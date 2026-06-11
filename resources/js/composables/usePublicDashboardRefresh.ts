import { useEcho } from "@laravel/echo-vue";
import type { PublicDashboardRefreshPayload } from "@/types/public";

export function usePublicDashboardRefresh(
    onRefresh: (payload: PublicDashboardRefreshPayload) => void,
): void {
    useEcho<PublicDashboardRefreshPayload>(
        "public.dashboard",
        ".dashboard.refresh",
        (payload) => onRefresh(payload),
    );
}
