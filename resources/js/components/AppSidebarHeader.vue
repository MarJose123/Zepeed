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
    <header class="flex h-14 shrink-0 items-center border-border/40 border-b">
        <div class="flex items-center gap-2 px-4 w-full">
            <SidebarTrigger class="-ml-1" />
            <Separator
                orientation="vertical"
                class="mr-2 data-[orientation=vertical]:h-3.5"
            />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
    </header>
</template>

<style scoped></style>
