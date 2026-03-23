<script setup lang="ts">
import Breadcrumbs from "@/components/Breadcrumbs.vue";

import { Separator } from "@/components/ui/separator";
import { SidebarTrigger } from "@/components/ui/sidebar";

import type { TBreadcrumbItem } from "@/types";

withDefaults(
    defineProps<{
        breadcrumbs?: TBreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header class="flex h-16 shrink-0 items-center gap-2">
        <div class="flex items-center gap-2 px-4 w-full">
            <SidebarTrigger class="-ml-1" />
            <Separator
                orientation="vertical"
                class="mr-2 data-[orientation=vertical]:h-4"
            />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
    </header>
</template>

<style scoped></style>
