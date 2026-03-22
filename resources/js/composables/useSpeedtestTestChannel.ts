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

export function useSpeedtestTestChannel(
    providerSlug: string,
    options: UseSpeedtestChannelOptions = {},
) {
    useEcho<SpeedtestEventPayload>(
        `speedtest.test.${providerSlug}`,
        ".speedtest.tes.started",
        (e) => options.onStarted?.(e),
    );

    useEcho<SpeedtestCompletedPayload>(
        `speedtest.test.${providerSlug}`,
        ".speedtest.test.completed",
        (e) => options.onCompleted?.(e),
    );

    useEcho<SpeedtestEventPayload>(
        `speedtest.test.${providerSlug}`,
        ".speedtest.test.failed",
        (e) => options.onFailed?.(e),
    );

    useEcho<SpeedtestSkippedPayload>(
        `speedtest.test.${providerSlug}`,
        ".speedtest.test.skipped",
        (e) => options.onSkipped?.(e),
    );

    useEcho<SpeedtestExceptionPayload>(
        `speedtest.test.${providerSlug}`,
        ".speedtest.test.exception",
        (e) => options.onException?.(e),
    );
}
