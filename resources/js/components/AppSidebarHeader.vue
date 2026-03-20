<script setup lang="ts">
import { Gauge } from "lucide-vue-next";
import Breadcrumbs from "@/components/Breadcrumbs.vue";

import DisplayModeToggle from "@/components/DisplayModeToggle.vue";
import Notification from "@/components/Notification.vue";
import { Button } from "@/components/ui/button";
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
            <div class="flex ml-auto">
                <Button variant="ghost" class="rounded-full">
                    <component :is="Gauge" />
                </Button>
                <Notification />
                <DisplayModeToggle />
            </div>
        </div>
    </header>
</template>

<style scoped></style>
