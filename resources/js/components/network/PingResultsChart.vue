<script setup lang="ts">
import { computed } from "vue";
import PingLineChart from "@/components/network/PingLineChart.vue";
import type {
    PingSeriesConfig,
    PingTarget,
    PingTrendBucket,
} from "@/types/ping";

const CHART_COLORS = [
    "var(--chart-1)",
    "var(--chart-2)",
    "var(--chart-3)",
    "var(--chart-4)",
    "var(--chart-5)",
] as const;

const props = defineProps<{
    trend: PingTrendBucket[];
    targets: PingTarget[];
}>();

type DataPoint = { label: string; [key: string]: number | string };

type TrendEntry = { avg_ms: number | null; loss: number | null };

const activeTargets = computed(() => {
    const idsInTrend = new Set<string>();

    for (const bucket of props.trend) {
        for (const key of Object.keys(bucket)) {
            if (key !== "label") idsInTrend.add(key);
        }
    }

    return props.targets.filter((t) => idsInTrend.has(t.id));
});

const series = computed<PingSeriesConfig[]>(() =>
    activeTargets.value.map((t, i) => ({
        key: t.id,
        label: t.label,
        color: CHART_COLORS[i % CHART_COLORS.length] ?? "var(--chart-1)",
    })),
);

const latencyPoints = computed<DataPoint[]>(() =>
    props.trend.map((bucket) => {
        const point: DataPoint = { label: bucket.label as string };

        for (const t of activeTargets.value) {
            const entry = bucket[t.id] as TrendEntry | undefined;
            point[t.id] = entry?.avg_ms ?? 0;
        }

        return point;
    }),
);

const lossPoints = computed<DataPoint[]>(() =>
    props.trend.map((bucket) => {
        const point: DataPoint = { label: bucket.label as string };

        for (const t of activeTargets.value) {
            const entry = bucket[t.id] as TrendEntry | undefined;
            point[t.id] = entry?.loss ?? 0;
        }

        return point;
    }),
);

const latencyFormatter = (v: number): string => `${v.toFixed(1)} ms`;
const lossFormatter = (v: number): string => `${v.toFixed(2)}%`;
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-2">
        <PingLineChart
            title="Latency Trend"
            description="Average round-trip time per target (ms)"
            :series="series"
            :points="latencyPoints"
            :tick-formatter="latencyFormatter"
        />
        <PingLineChart
            title="Packet Loss Trend"
            description="Packet loss percentage per target"
            :series="series"
            :points="lossPoints"
            :tick-formatter="lossFormatter"
        />
    </div>
</template>
