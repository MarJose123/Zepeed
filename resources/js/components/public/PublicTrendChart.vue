<script setup lang="ts">
import { computed } from "vue";
import type { TrendPoint } from "@/types/public";

const props = defineProps<{
    points: TrendPoint[];
    metric: "download" | "upload" | "ping";
    label: string;
    unit: string;
}>();

const values = computed(() => props.points.map((p) => p[props.metric]));

const currentValue = computed(() => {
    if (values.value.length === 0) return 0;

    return values.value[values.value.length - 1];
});

const max = computed(() => Math.max(...values.value, 1));

const barHeights = computed(() =>
    values.value.map((v) => Math.max(8, Math.round((v / max.value) * 100))),
);

const colorMap: Record<string, string> = {
    download: "bg-blue-400",
    upload: "bg-green-500",
    ping: "bg-amber-400",
};

const barColor = computed(() => colorMap[props.metric]);
</script>

<template>
    <div class="bg-card border-border rounded-lg border p-3.5">
        <div class="mb-2.5 flex items-center justify-between">
            <p class="text-sm font-bold">{{ label }}</p>
            <p class="text-sm font-bold">
                {{ currentValue
                }}<span
                    class="text-muted-foreground ml-1 text-xs font-normal"
                    >{{ unit }}</span
                >
            </p>
        </div>
        <div v-if="barHeights.length > 0" class="flex h-12 items-end gap-px">
            <div
                v-for="(height, i) in barHeights"
                :key="i"
                :class="barColor"
                :style="{ height: `${height}%` }"
                class="flex-1 rounded-sm opacity-80"
            />
        </div>
        <div
            v-else
            class="text-muted-foreground flex h-12 items-center justify-center text-xs"
        >
            No data yet
        </div>
        <p class="text-muted-foreground mt-1.5 text-[10px]">
            Average across all providers · last 24 h
        </p>
    </div>
</template>
