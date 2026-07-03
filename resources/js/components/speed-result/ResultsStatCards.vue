<script setup lang="ts">
import {
    ArrowDownToLine,
    ArrowUpFromLine,
    Gauge,
    ShieldAlert,
    TrendingDown,
    TrendingUp,
} from "@lucide/vue";
import { computed } from "vue";
import StatCard from "@/components/speed-result/StatCard.vue";
import type { TSpeedResultStats } from "@/types/speed-result";

const props = defineProps<{
    stats: TSpeedResultStats;
    metric: "download" | "upload" | "ping";
    accentVar: string;
}>();

const unit = computed(() => (props.metric === "ping" ? "ms" : "Mbps"));
const metricLabel = computed(() => {
    if (props.metric === "download") return "Download";

    if (props.metric === "upload") return "Upload";

    return "Ping";
});

const primaryIcon = computed(() => {
    if (props.metric === "download") return ArrowDownToLine;

    if (props.metric === "upload") return ArrowUpFromLine;

    return Gauge;
});

const isPing = computed(() => props.metric === "ping");
const bestValue = computed(() =>
    isPing.value ? props.stats.best : props.stats.peak,
);
const worstValue = computed(() =>
    isPing.value ? props.stats.worst : props.stats.lowest,
);

const cards = computed(() => [
    {
        label: `Average ${metricLabel.value}`,
        value: props.stats.average,
        unit: unit.value,
        subLabel: `across ${props.stats.total} tests`,
        icon: primaryIcon.value,
        tone: "accent" as const,
    },
    {
        label: isPing.value
            ? "Best (lowest) Ping"
            : `Peak ${metricLabel.value}`,
        value: bestValue.value,
        unit: unit.value,
        subLabel: "best recorded value",
        icon: isPing.value ? TrendingDown : TrendingUp,
        tone: "success" as const,
    },
    {
        label: isPing.value
            ? "Worst (highest) Ping"
            : `Lowest ${metricLabel.value}`,
        value: worstValue.value,
        unit: unit.value,
        subLabel: isPing.value
            ? "highest recorded value"
            : "lowest recorded value",
        icon: isPing.value ? TrendingUp : TrendingDown,
        tone: "destructive" as const,
    },
    {
        label: props.stats.threshold_label,
        value: props.stats.threshold_count,
        unit: "tests",
        subLabel: `${props.stats.threshold_pct}% of total tests`,
        icon: ShieldAlert,
        tone: "warning" as const,
    },
]);
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
            v-for="card in cards"
            :key="card.label"
            :label="card.label"
            :value="card.value"
            :unit="card.unit"
            :sub-label="card.subLabel"
            :icon="card.icon"
            :tone="card.tone"
            :accent-var="accentVar"
        />
    </div>
</template>
