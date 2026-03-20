<script setup lang="ts">
import AppContent from "@/components/AppContent.vue";
import AppSidebar from "@/components/AppSidebar.vue";
import AppSidebarHeader from "@/components/AppSidebarHeader.vue";
import AppSidebarProvider from "@/components/AppSidebarProvider.vue";
import type { TBreadcrumbItem } from "@/types";

type Props = {
    breadcrumbs?: TBreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppSidebarProvider>
        <AppSidebar />
        <AppContent class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppSidebarProvider>
</template>

<style scoped></style>
