<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";
import { Toaster } from "vue-sonner";
import { useNotification } from "@/composables/useNotification";
import { useNotificationChannel } from "@/composables/useNotificationChannel";
import { useSpeedtestTestChannel } from "@/composables/useSpeedtestTestChannel";
import AppSidebarLayout from "@/layouts/app/AppSidebarLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type { Provider } from "@/types/provider";

type Props = {
    breadcrumbs?: TBreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const flashEventListener = ref();
const page = usePage();
const { notify } = useNotification();

const providers = page.props.speedtest as Provider[];

providers.forEach((provider) => {
    useSpeedtestTestChannel(provider.slug, {
        onCompleted: (payload) => {
            notify({
                type: "success",
                title: `${provider.name} test completed`,
                message: `↓ ${payload.download_mbps} Mbps  ↑ ${payload.upload_mbps} Mbps  ↔ ping ${payload.ping_ms} ms`,
            });
        },
        onException: (payload) => {
            notify({
                type: "error",
                title: `${provider.name} test failed`,
                message: payload.message,
            });
        },
        onSkipped: () => {
            notify({
                type: "warning",
                title: `${provider.name} test skipped`,
                message: "Maintenance window is currently active.",
            });
        },
        onCancelled: () => {
            notify({
                type: "info",
                title: `${provider.name} test cancelled`,
                message: "The test was cancelled.",
            });
        },
    });
});

onMounted(() => {
    flashEventListener.value = router.on("flash", (event) => {
        notify(event.detail.flash?.notification);
    });
});

onUnmounted(() => {
    if (flashEventListener.value) {
        flashEventListener.value();
    }
});

useNotificationChannel(); // ← mounts the export broadcast listener
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
    <Toaster richColors position="bottom-right" />
</template>
