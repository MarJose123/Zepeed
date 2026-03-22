import { useEcho } from "@laravel/echo-vue";
import type {
    SpeedtestEventPayload,
    SpeedtestCompletedPayload,
    SpeedtestExceptionPayload,
    SpeedtestSkippedPayload,
} from "@/types/speedtest";

interface UseSpeedtestChannelOptions {
    onStarted?: (payload: SpeedtestEventPayload) => void;
    onCompleted?: (payload: SpeedtestCompletedPayload) => void;
    onFailed?: (payload: SpeedtestEventPayload) => void;
    onSkipped?: (payload: SpeedtestSkippedPayload) => void;
    onException?: (payload: SpeedtestExceptionPayload) => void;
}

export function useSpeedtestChannel(
    providerSlug: string,
    options: UseSpeedtestChannelOptions = {},
) {
    useEcho<SpeedtestEventPayload>(
        `speedtest.${providerSlug}`,
        ".speedtest.started",
        (e) => options.onStarted?.(e),
    );

    useEcho<SpeedtestCompletedPayload>(
        `speedtest.${providerSlug}`,
        ".speedtest.completed",
        (e) => options.onCompleted?.(e),
    );

    useEcho<SpeedtestEventPayload>(
        `speedtest.${providerSlug}`,
        ".speedtest.failed",
        (e) => options.onFailed?.(e),
    );

    useEcho<SpeedtestSkippedPayload>(
        `speedtest.${providerSlug}`,
        ".speedtest.skipped",
        (e) => options.onSkipped?.(e),
    );

    useEcho<SpeedtestExceptionPayload>(
        `speedtest.${providerSlug}`,
        ".speedtest.exception",
        (e) => options.onException?.(e),
    );
}
