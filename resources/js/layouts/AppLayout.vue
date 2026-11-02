<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
import { echo } from "@laravel/echo-vue";
import { onMounted, onUnmounted, ref, watch } from "vue";
import DroppingSpiders from "@/components/DroppingSpiders.vue";
import FallingSnowflakes from "@/components/FallingSnowflakes.vue";
import Fireworks from "@/components/Fireworks.vue";
import GitHubStarDialog from "@/components/GitHubStarDialog.vue";
import SpiderWeb from "@/components/SpiderWeb.vue";
import { Sonner } from "@/components/ui/sonner";
import {
    updateSeasonalThemeClass,
    useAppearance,
} from "@/composables/useAppearance";
import { useNotification } from "@/composables/useNotification";
import { useNotificationChannel } from "@/composables/useNotificationChannel";
import AppSidebarLayout from "@/layouts/app/AppSidebarLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type {
    SpeedtestCompletedPayload,
    SpeedtestExceptionPayload,
} from "@/types/provider";

type Props = {
    breadcrumbs?: TBreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const flashEventListener = ref();
const channelUnsubscribes: Array<() => void> = [];

const page = usePage();
const { notify } = useNotification();
const { seasonalTheme } = useAppearance();

// Wait for deferred providers to load before setting up WebSocket channels
// Use raw Echo subscriptions instead of the composable to avoid lifecycle hook warnings
watch(
    () => page.props.speedtest,
    (speedtestData) => {
        if (!Array.isArray(speedtestData)) {
            return;
        }

        // Clean up previous subscriptions to avoid duplicates on re-renders
        channelUnsubscribes.forEach((unsubscribe) => unsubscribe());
        channelUnsubscribes.length = 0;

        speedtestData.forEach((provider) => {
            const ch = echo().channel(`speedtest.test.${provider.slug}`);

            channelUnsubscribes.push(() => {
                ch.stopListening(".speedtest.test.started");
                ch.stopListening(".speedtest.test.completed");
                ch.stopListening(".speedtest.test.failed");
                ch.stopListening(".speedtest.test.skipped");
                ch.stopListening(".speedtest.test.exception");
                ch.stopListening(".speedtest.test.cancelled");
                echo().leaveChannel(`speedtest.test.${provider.slug}`);
            });

            ch.listen(
                ".speedtest.test.completed",
                (payload: SpeedtestCompletedPayload) => {
                    notify({
                        type: "success",
                        title: `${provider.name} test completed`,
                        message: `↓ ${payload.download_mbps} Mbps  ↑ ${payload.upload_mbps} Mbps  ↔ ping ${payload.ping_ms} ms`,
                    });
                },
            );

            ch.listen(
                ".speedtest.test.exception",
                (payload: SpeedtestExceptionPayload) => {
                    notify({
                        type: "error",
                        title: `${provider.name} test failed`,
                        message: payload.message,
                    });
                },
            );

            ch.listen(".speedtest.test.skipped", () => {
                notify({
                    type: "warning",
                    title: `${provider.name} test skipped`,
                    message: "Maintenance window is currently active.",
                });
            });

            ch.listen(".speedtest.test.cancelled", () => {
                notify({
                    type: "info",
                    title: `${provider.name} test cancelled`,
                    message: "The test was cancelled.",
                });
            });
        });
    },
    { immediate: true },
);

onMounted(() => {
    flashEventListener.value = router.on("flash", (event) => {
        notify(event.detail.flash?.notification);
    });

    // Apply seasonal theme class only for authenticated pages
    if (seasonalTheme.value !== "none") {
        updateSeasonalThemeClass(seasonalTheme.value);
    }
});

onUnmounted(() => {
    if (flashEventListener.value) {
        flashEventListener.value();
    }

    // Clean up all speedtest channel subscriptions
    channelUnsubscribes.forEach((unsubscribe) => unsubscribe());
    channelUnsubscribes.length = 0;
});

useNotificationChannel(); // ← mounts the export broadcast listener
</script>

<template>
    <FallingSnowflakes />
    <Fireworks />
    <DroppingSpiders />
    <SpiderWeb />
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
    <GitHubStarDialog />
    <Sonner richColors position="bottom-right" />
</template>

<style>
/* Christmas seasonal theme effects */
.theme-christmas [data-slot="card"] {
    border-color: rgba(180, 210, 255, 0.5);
    box-shadow:
        0 0 0 1px rgba(200, 225, 255, 0.3),
        0 0 12px rgba(180, 210, 255, 0.25),
        0 1px 3px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.theme-christmas [data-slot="card"]::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: inherit;
    background: linear-gradient(
        135deg,
        rgba(200, 225, 255, 0.08) 0%,
        transparent 50%,
        rgba(200, 225, 255, 0.05) 100%
    );
    pointer-events: none;
    z-index: 0;
}

.theme-christmas [data-slot="table-container"] {
    border-color: rgba(180, 210, 255, 0.5);
    box-shadow:
        0 0 0 1px rgba(200, 225, 255, 0.3),
        0 0 12px rgba(180, 210, 255, 0.25),
        0 1px 3px rgba(0, 0, 0, 0.1);
    border-radius: 0.75rem;
    border: 1px solid;
    position: relative;
    overflow: hidden;
}

.theme-christmas [data-slot="table-container"]::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: inherit;
    background: linear-gradient(
        135deg,
        rgba(200, 225, 255, 0.08) 0%,
        transparent 50%,
        rgba(200, 225, 255, 0.05) 100%
    );
    pointer-events: none;
    z-index: 0;
}

.dark.theme-christmas [data-slot="card"] {
    border-color: rgba(160, 195, 255, 0.4);
    box-shadow:
        0 0 0 1px rgba(180, 210, 255, 0.2),
        0 0 16px rgba(160, 195, 255, 0.3),
        0 1px 3px rgba(0, 0, 0, 0.3);
}

.dark.theme-christmas [data-slot="card"]::before {
    background: linear-gradient(
        135deg,
        rgba(160, 195, 255, 0.06) 0%,
        transparent 50%,
        rgba(160, 195, 255, 0.04) 100%
    );
}

.dark.theme-christmas [data-slot="table-container"] {
    border-color: rgba(160, 195, 255, 0.4);
    box-shadow:
        0 0 0 1px rgba(180, 210, 255, 0.2),
        0 0 16px rgba(160, 195, 255, 0.3),
        0 1px 3px rgba(0, 0, 0, 0.3);
}

.dark.theme-christmas [data-slot="table-container"]::before {
    background: linear-gradient(
        135deg,
        rgba(160, 195, 255, 0.06) 0%,
        transparent 50%,
        rgba(160, 195, 255, 0.04) 100%
    );
}

/* Halloween seasonal theme effects */
.theme-halloween [data-slot="card"] {
    border-color: rgba(255, 140, 0, 0.4);
    box-shadow:
        0 0 0 1px rgba(255, 140, 0, 0.2),
        0 0 12px rgba(255, 140, 0, 0.15),
        0 1px 3px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.theme-halloween [data-slot="card"]::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: inherit;
    background: linear-gradient(
        135deg,
        rgba(255, 140, 0, 0.05) 0%,
        transparent 50%,
        rgba(150, 50, 200, 0.03) 100%
    );
    pointer-events: none;
    z-index: 0;
}

.theme-halloween [data-slot="table-container"] {
    border-color: rgba(255, 140, 0, 0.4);
    box-shadow:
        0 0 0 1px rgba(255, 140, 0, 0.2),
        0 0 12px rgba(255, 140, 0, 0.15),
        0 1px 3px rgba(0, 0, 0, 0.1);
    border-radius: 0.75rem;
    border: 1px solid;
    position: relative;
    overflow: hidden;
}

.theme-halloween [data-slot="table-container"]::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: inherit;
    background: linear-gradient(
        135deg,
        rgba(255, 140, 0, 0.05) 0%,
        transparent 50%,
        rgba(150, 50, 200, 0.03) 100%
    );
    pointer-events: none;
    z-index: 0;
}

.dark.theme-halloween [data-slot="card"] {
    border-color: rgba(255, 140, 0, 0.3);
    box-shadow:
        0 0 0 1px rgba(255, 140, 0, 0.15),
        0 0 16px rgba(255, 140, 0, 0.2),
        0 1px 3px rgba(0, 0, 0, 0.3);
}

.dark.theme-halloween [data-slot="card"]::before {
    background: linear-gradient(
        135deg,
        rgba(255, 140, 0, 0.04) 0%,
        transparent 50%,
        rgba(150, 50, 200, 0.02) 100%
    );
}

.dark.theme-halloween [data-slot="table-container"] {
    border-color: rgba(255, 140, 0, 0.3);
    box-shadow:
        0 0 0 1px rgba(255, 140, 0, 0.15),
        0 0 16px rgba(255, 140, 0, 0.2),
        0 1px 3px rgba(0, 0, 0, 0.3);
}

.dark.theme-halloween [data-slot="table-container"]::before {
    background: linear-gradient(
        135deg,
        rgba(255, 140, 0, 0.04) 0%,
        transparent 50%,
        rgba(150, 50, 200, 0.02) 100%
    );
}

/* New Year seasonal theme effects */
.theme-newyear [data-slot="card"] {
    border-color: rgba(255, 215, 0, 0.4);
    box-shadow:
        0 0 0 1px rgba(255, 215, 0, 0.2),
        0 0 12px rgba(255, 215, 0, 0.15),
        0 1px 3px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.theme-newyear [data-slot="card"]::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: inherit;
    background: linear-gradient(
        135deg,
        rgba(255, 215, 0, 0.05) 0%,
        transparent 50%,
        rgba(160, 130, 255, 0.03) 100%
    );
    pointer-events: none;
    z-index: 0;
}

.theme-newyear [data-slot="table-container"] {
    border-color: rgba(255, 215, 0, 0.4);
    box-shadow:
        0 0 0 1px rgba(255, 215, 0, 0.2),
        0 0 12px rgba(255, 215, 0, 0.15),
        0 1px 3px rgba(0, 0, 0, 0.1);
    border-radius: 0.75rem;
    border: 1px solid;
    position: relative;
    overflow: hidden;
}

.theme-newyear [data-slot="table-container"]::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: inherit;
    background: linear-gradient(
        135deg,
        rgba(255, 215, 0, 0.05) 0%,
        transparent 50%,
        rgba(160, 130, 255, 0.03) 100%
    );
    pointer-events: none;
    z-index: 0;
}

.dark.theme-newyear [data-slot="card"] {
    border-color: rgba(255, 215, 0, 0.3);
    box-shadow:
        0 0 0 1px rgba(255, 215, 0, 0.15),
        0 0 16px rgba(255, 215, 0, 0.2),
        0 1px 3px rgba(0, 0, 0, 0.3);
}

.dark.theme-newyear [data-slot="card"]::before {
    background: linear-gradient(
        135deg,
        rgba(255, 215, 0, 0.04) 0%,
        transparent 50%,
        rgba(160, 130, 255, 0.02) 100%
    );
}

.dark.theme-newyear [data-slot="table-container"] {
    border-color: rgba(255, 215, 0, 0.3);
    box-shadow:
        0 0 0 1px rgba(255, 215, 0, 0.15),
        0 0 16px rgba(255, 215, 0, 0.2),
        0 1px 3px rgba(0, 0, 0, 0.3);
}

.dark.theme-newyear [data-slot="table-container"]::before {
    background: linear-gradient(
        135deg,
        rgba(255, 215, 0, 0.04) 0%,
        transparent 50%,
        rgba(160, 130, 255, 0.02) 100%
    );
}
</style>
