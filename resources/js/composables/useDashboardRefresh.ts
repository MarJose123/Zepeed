import { useEchoPresence } from "@laravel/echo-vue";
import type { DashboardRefreshPayload } from "@/types/dashboard";

export function useDashboardRefresh(onRefresh: () => void): void {
    useEchoPresence<DashboardRefreshPayload>(
        "dashboard",
        ".dashboard.refresh",
        () => onRefresh(),
    );
}
