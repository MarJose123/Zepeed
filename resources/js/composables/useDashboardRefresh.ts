import { useEcho } from "@laravel/echo-vue";
import type { DashboardRefreshPayload } from "@/types/dashboard";

export function useDashboardRefresh(onRefresh: () => void): void {
    useEcho<DashboardRefreshPayload>("dashboard", ".dashboard.refresh", () =>
        onRefresh(),
    );
}
