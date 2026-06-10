import { useHttp } from "@inertiajs/vue3";
import { useEcho } from "@laravel/echo-vue";
import type {
    SpeedtestCompletedPayload,
    SpeedtestEventPayload,
    SpeedtestExceptionPayload,
    SpeedtestSkippedPayload,
    SpeedtestTestCancelledPayload,
} from "@/types/provider";

interface UseSpeedtestTestChannelOptions {
    onStarted?: (payload: SpeedtestEventPayload) => void;
    onCompleted?: (payload: SpeedtestCompletedPayload) => void;
    onFailed?: (payload: SpeedtestEventPayload) => void;
    onSkipped?: (payload: SpeedtestSkippedPayload) => void;
    onException?: (payload: SpeedtestExceptionPayload) => void;
    onCancelled?: (payload: SpeedtestTestCancelledPayload) => void;
}

export function useSpeedtestTestChannel(
    providerSlug: string,
    options: UseSpeedtestTestChannelOptions = {},
) {
    useEcho<SpeedtestEventPayload>(
        `speedtest.test.${providerSlug}`,
        ".speedtest.test.started",
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

    useEcho<SpeedtestTestCancelledPayload>(
        `speedtest.test.${providerSlug}`,
        ".speedtest.test.cancelled",
        (e) => options.onCancelled?.(e),
    );
}

/**
 * Composable that exposes startTest and cancelTest as useHttp-backed methods.
 * Must be called inside a component <script setup> context.
 */
export function useProviderTestHttp() {
    const startHttp = useHttp({});
    const cancelHttp = useHttp({});

    async function startTest(
        providerSlug: string,
    ): Promise<{ test_session_id: string; provider_slug: string } | null> {
        try {
            const data = await startHttp.post(
                route("speedtest.server.providers.test", {
                    provider: providerSlug,
                }),
            );

            return data as { test_session_id: string; provider_slug: string };
        } catch {
            return null;
        }
    }

    async function cancelTest(
        providerSlug: string,
        testSessionId: string,
    ): Promise<boolean> {
        try {
            await cancelHttp.delete(
                route("speedtest.server.providers.test.cancel", {
                    provider: providerSlug,
                    testSessionId,
                }),
            );

            return true;
        } catch {
            return false;
        }
    }

    return { startTest, cancelTest };
}
