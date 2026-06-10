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
 * POST /speedtest/settings/server/providers/{provider}/test
 * Returns 202 with { test_session_id, provider_slug }.
 */
export async function startProviderTest(
    providerSlug: string,
): Promise<{ test_session_id: string; provider_slug: string } | null> {
    const csrf =
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? "";

    const res = await fetch(
        route("speedtest.server.providers.test", { provider: providerSlug }),
        {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf,
            },
        },
    );

    if (res.status === 202) {
        return res.json() as Promise<{
            test_session_id: string;
            provider_slug: string;
        }>;
    }

    return null;
}

/**
 * DELETE /speedtest/settings/server/providers/{provider}/test/{testSessionId}
 */
export async function cancelProviderTest(
    providerSlug: string,
    testSessionId: string,
): Promise<boolean> {
    const csrf =
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? "";

    const res = await fetch(
        route("speedtest.server.providers.test.cancel", {
            provider: providerSlug,
            testSessionId,
        }),
        {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf,
            },
        },
    );

    return res.ok;
}
