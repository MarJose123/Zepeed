import { useEchoPresence } from "@laravel/echo-vue";
import type { DashboardRefreshPayload } from "@/types/dashboard";

/**
 * Listens on the presence channel `dashboard` for `dashboard.refresh`
 * events broadcast after every successful scheduled speedtest run.
 * Presence channel requires authenticated users; the backend
 * authorises via routes/channels.php Broadcast::channel('dashboard').
 */
export function useDashboardRefresh(onRefresh: () => void): void {
    useEchoPresence<DashboardRefreshPayload>(
        "dashboard",
        ".dashboard.refresh",
        () => onRefresh(),
    );
}
