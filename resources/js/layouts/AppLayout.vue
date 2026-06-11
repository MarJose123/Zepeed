<script setup lang="ts">
import { usePage, router } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";
import { Toaster } from "vue-sonner";
import { useNotification } from "@/composables/useNotification";
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

const page = usePage();
const { notify } = useNotification();
const flashEventListener = ref();

// Subscribe to every enabled provider's test channel once, at the layout level.
// This ensures test result toasts are visible regardless of which page the user
// navigated to after clicking Test.
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
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
    <Toaster richColors position="bottom-right" />
</template>

<style scoped></style>
