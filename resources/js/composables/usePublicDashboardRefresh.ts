import { useEchoPublic } from "@laravel/echo-vue";
import type { PublicDashboardRefreshPayload } from "@/types/public";

export function usePublicDashboardRefresh(
    onRefresh: (payload: PublicDashboardRefreshPayload) => void,
): void {
    useEchoPublic<PublicDashboardRefreshPayload>(
        "public.dashboard",
        ".dashboard.refresh",
        (payload) => onRefresh(payload),
    );
}
