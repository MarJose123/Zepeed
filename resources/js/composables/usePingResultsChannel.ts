import { useEcho } from "@laravel/echo-vue";

export interface PingResultCompletedPayload {
    target_id: string;
    target_label: string;
    target_host: string;
    status: string;
    avg_ms: number | null;
    packet_loss_percent: number | null;
    measured_at: string;
}

interface UsePingResultsChannelOptions {
    onCompleted?: (payload: PingResultCompletedPayload) => void;
}

export function usePingResultsChannel(
    options: UsePingResultsChannelOptions = {},
): void {
    useEcho<PingResultCompletedPayload>(
        "ping.results",
        ".ping.result.completed",
        (payload) => options.onCompleted?.(payload),
    );
}
