import { useEchoPublic } from "@laravel/echo-vue";
import type { PublicPingRefreshPayload } from "@/types/public";

export function usePublicPingRefresh(
    onRefresh: (payload: PublicPingRefreshPayload) => void,
): void {
    useEchoPublic<PublicPingRefreshPayload>(
        "public.dashboard",
        ".ping.result.completed",
        (payload) => onRefresh(payload),
    );
}
