<script setup lang="ts">
import { computed } from "vue";
import { Badge } from "@/components/ui/badge";
import type { PingStatus } from "@/types/ping";

const props = defineProps<{ status: PingStatus }>();

const config = computed(
    () =>
        ({
            ok: {
                label: "OK",
                class: "border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300",
            },
            warn: {
                label: "Warning",
                class: "border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300",
            },
            failed: {
                label: "Failed",
                class: "border-red-300 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300",
            },
            pending: {
                label: "Pending",
                class: "border-border bg-muted text-muted-foreground",
            },
        })[props.status],
);
</script>

<template>
    <Badge variant="outline" class="text-xs" :class="config.class">
        {{ config.label }}
    </Badge>
</template>
