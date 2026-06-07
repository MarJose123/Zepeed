<script setup lang="ts">
import { ArrowDown, ArrowUp, Zap } from "@lucide/vue";
import { computed } from "vue";
import { Card, CardContent } from "@/components/ui/card";
import type { TrendPoint } from "@/types/public";

const props = defineProps<{
    points: TrendPoint[];
    metric: "download" | "upload" | "ping";
    label: string;
    unit: string;
}>();

const iconMap = {
    download: ArrowDown,
    upload: ArrowUp,
    ping: Zap,
};

const IconComponent = computed(() => iconMap[props.metric]);

const paddedBars = computed(() => {
    const total = 24;
    const filled = props.points.map((p) => ({
        value: p[props.metric],
        isEmpty: false,
    }));

    if (filled.length >= total) return filled.slice(-total);

    const padding = Array.from({ length: total - filled.length }, () => ({
        value: 0,
        isEmpty: true,
    }));

    return [...padding, ...filled];
});

const max = computed(() =>
    Math.max(...paddedBars.value.map((b) => b.value), 1),
);

const bars = computed(() =>
    paddedBars.value.map((b) => ({
        height: b.isEmpty
            ? 3
            : Math.max(6, Math.round((b.value / max.value) * 100)),
        isEmpty: b.isEmpty,
    })),
);

const currentValue = computed(() => {
    const real = props.points;

    return real.length > 0 ? real[real.length - 1][props.metric] : 0;
});

const colorClass: Record<string, string> = {
    download: "bg-blue-400",
    upload: "bg-green-500",
    ping: "bg-amber-400",
};

const emptyClass: Record<string, string> = {
    download: "bg-blue-100 dark:bg-blue-950",
    upload: "bg-green-100 dark:bg-green-950",
    ping: "bg-amber-100 dark:bg-amber-950",
};
</script>

<template>
    <Card class="rounded-lg">
        <CardContent class="p-3">
            <div class="mb-2.5 flex items-center justify-between">
                <p class="flex items-center gap-1.5 text-sm font-bold">
                    <component
                        :is="IconComponent"
                        class="h-3.5 w-3.5"
                        aria-hidden="true"
                    />
                    {{ label }}
                </p>
                <p class="text-sm font-bold">
                    {{ currentValue
                    }}<span
                        class="text-muted-foreground ml-1 text-xs font-normal"
                        >{{ unit }}</span
                    >
                </p>
            </div>
            <div class="flex h-12 items-end gap-px">
                <div
                    v-for="(bar, i) in bars"
                    :key="i"
                    :class="
                        bar.isEmpty ? emptyClass[metric] : colorClass[metric]
                    "
                    :style="{ height: `${bar.height}%` }"
                    class="flex-1 rounded-sm"
                />
            </div>
            <p class="text-muted-foreground mt-1.5 text-[10px]">
                Average across all providers · last 24 h
            </p>
        </CardContent>
    </Card>
</template>
