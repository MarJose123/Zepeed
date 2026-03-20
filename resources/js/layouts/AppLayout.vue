<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";
import { Toaster } from "vue-sonner";
import { useNotification } from "@/composables/useNotification";
import AppSidebarLayout from "@/layouts/app/AppSidebarLayout.vue";
import type { TBreadcrumbItem } from "@/types";

type Props = {
    breadcrumbs?: TBreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const flashEventListener = ref();

const { notify } = useNotification();

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
