<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
import { echo } from "@laravel/echo-vue";
import { onMounted, onUnmounted, ref, watch } from "vue";
import GitHubStarDialog from "@/components/GitHubStarDialog.vue";
import { Sonner } from "@/components/ui/sonner";
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
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
    <GitHubStarDialog />
    <Sonner richColors position="bottom-right" />
</template>
