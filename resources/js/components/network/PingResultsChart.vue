<script setup lang="ts">
import { VisAxis, VisLine, VisXYContainer } from "@unovis/vue";
import { computed } from "vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    ChartContainer,
    ChartCrosshair,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
    componentToString,
} from "@/components/ui/chart";
import type { ChartConfig } from "@/components/ui/chart";
import type { PingTarget, PingTrendBucket } from "@/types/ping";

const props = defineProps<{
    trend: PingTrendBucket[];
    targets: PingTarget[];
}>();

// ── Colour palette — mirrors chart CSS variables ──────────────────────────────
const CHART_COLORS = [
    "var(--chart-1)",
    "var(--chart-2)",
    "var(--chart-3)",
    "var(--chart-4)",
    "var(--chart-5)",
] as const;

// ── Derive the set of active targets present in the trend data ────────────────
// (only targets that actually have at least one bucket)
const activeTargets = computed(() => {
    const idsInTrend = new Set<string>();

    for (const bucket of props.trend) {
        for (const key of Object.keys(bucket)) {
            if (key !== "label") idsInTrend.add(key);
        }
    }

    return props.targets.filter((t) => idsInTrend.has(t.id));
});

// ── ChartConfig — one entry per target, drives colour + legend ────────────────
const latencyConfig = computed<ChartConfig>(() =>
    Object.fromEntries(
        activeTargets.value.map((t, i) => [
            t.id,
            {
                label: t.label,
                color: CHART_COLORS[i % CHART_COLORS.length],
            },
        ]),
    ),
);

// Packet loss uses the same config (same targets, same colours)
const lossConfig = computed<ChartConfig>(() => latencyConfig.value);

// ── Data point shape ──────────────────────────────────────────────────────────
// Each point: { label, [targetId]: value }
type DataPoint = { label: string; [key: string]: number | string };

const latencyPoints = computed<DataPoint[]>(() =>
    props.trend.map((bucket) => {
        const point: DataPoint = { label: bucket.label as string };

        for (const target of activeTargets.value) {
            const entry = bucket[target.id] as
                | { avg_ms: number | null; loss: number | null }
                | undefined;
            point[target.id] = entry?.avg_ms ?? 0;
        }

        return point;
    }),
);

const lossPoints = computed<DataPoint[]>(() =>
    props.trend.map((bucket) => {
        const point: DataPoint = { label: bucket.label as string };

        for (const target of activeTargets.value) {
            const entry = bucket[target.id] as
                | { avg_ms: number | null; loss: number | null }
                | undefined;
            point[target.id] = entry?.loss ?? 0;
        }

        return point;
    }),
);

// ── Unovis accessors ──────────────────────────────────────────────────────────
const xAccessor = (_d: DataPoint, i: number) => i;

const latencyYAccessors = computed(() =>
    activeTargets.value.map((t) => (d: DataPoint) => (d[t.id] as number) ?? 0),
);

const lossYAccessors = computed(() =>
    activeTargets.value.map((t) => (d: DataPoint) => (d[t.id] as number) ?? 0),
);

const colorAccessor = computed(
    () => (_d: DataPoint, i: number) =>
        CHART_COLORS[i % CHART_COLORS.length] ?? "var(--chart-1)",
);

const latencyColors = computed(() =>
    activeTargets.value.map((_, i) => CHART_COLORS[i % CHART_COLORS.length]),
);

const lossColors = computed(() => latencyColors.value);

const xTickFormat = (i: number) => latencyPoints.value[i]?.label ?? "";
const latencyYFormat = (v: number) => `${v}`;
const lossYFormat = (v: number) => `${v}%`;
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-2">
        <!-- Latency Trend -->
        <Card class="overflow-hidden p-0">
            <CardHeader class="border-border border-b px-4 py-3">
                <CardTitle class="text-sm font-medium">Latency Trend</CardTitle>
                <CardDescription class="text-[11px]">
                    Average round-trip time per target (ms)
                </CardDescription>
            </CardHeader>
            <CardContent class="px-4 pb-2 pt-4">
                <ChartContainer
                    :config="latencyConfig"
                    class="w-full"
                    style="height: 270px"
                    :cursor="false"
                >
                    <VisXYContainer :data="latencyPoints" style="height: 210px">
                        <VisLine
                            :fallbackValue="0"
                            :x="xAccessor"
                            :y="latencyYAccessors"
                            :color="colorAccessor"
                            curve-type="basis"
                        />
                        <VisAxis
                            type="x"
                            :x="xAccessor"
                            :tick-format="xTickFormat"
                            :tick-line="false"
                            :domain-line="false"
                            :grid-line="false"
                            :num-ticks="6"
                        />
                        <VisAxis
                            type="y"
                            :tick-format="latencyYFormat"
                            :tick-line="false"
                            :domain-line="false"
                            :grid-line="true"
                            :num-ticks="4"
                        />
                        <ChartTooltip />
                        <ChartCrosshair
                            :color="latencyColors"
                            :template="
                                componentToString(
                                    latencyConfig,
                                    ChartTooltipContent,
                                    {
                                        indicator: 'line',
                                        labelFormatter: (i: number | Date) =>
                                            (latencyPoints as any)[Number(i)]
                                                ?.label ?? undefined,
                                        payload: (
                                            originalPayload: Record<
                                                string,
                                                any
                                            >,
                                        ) => {
                                            const formatted: Record<
                                                string,
                                                any
                                            > = {};
                                            Object.entries(
                                                originalPayload,
                                            ).forEach(([key, value]) => {
                                                formatted[key] =
                                                    typeof value === 'number'
                                                        ? `${value.toFixed(1)} ms`
                                                        : value;
                                            });
                                            return formatted;
                                        },
                                    },
                                )
                            "
                        />
                    </VisXYContainer>

                    <div
                        class="border-border mt-1 flex items-center justify-center border-t pt-2"
                    >
                        <ChartLegendContent />
                    </div>
                </ChartContainer>
            </CardContent>
        </Card>

        <!-- Packet Loss Trend -->
        <Card class="overflow-hidden p-0">
            <CardHeader class="border-border border-b px-4 py-3">
                <CardTitle class="text-sm font-medium">
                    Packet Loss Trend
                </CardTitle>
                <CardDescription class="text-[11px]">
                    Packet loss percentage per target
                </CardDescription>
            </CardHeader>
            <CardContent class="px-4 pb-2 pt-4">
                <ChartContainer
                    :config="lossConfig"
                    class="w-full"
                    style="height: 270px"
                    :cursor="false"
                >
                    <VisXYContainer :data="lossPoints" style="height: 210px">
                        <VisLine
                            :fallbackValue="0"
                            :x="xAccessor"
                            :y="lossYAccessors"
                            :color="colorAccessor"
                            curve-type="basis"
                        />
                        <VisAxis
                            type="x"
                            :x="xAccessor"
                            :tick-format="xTickFormat"
                            :tick-line="false"
                            :domain-line="false"
                            :grid-line="false"
                            :num-ticks="6"
                        />
                        <VisAxis
                            type="y"
                            :tick-format="lossYFormat"
                            :tick-line="false"
                            :domain-line="false"
                            :grid-line="true"
                            :num-ticks="4"
                        />
                        <ChartTooltip />
                        <ChartCrosshair
                            :color="lossColors"
                            :template="
                                componentToString(
                                    lossConfig,
                                    ChartTooltipContent,
                                    {
                                        indicator: 'line',
                                        labelFormatter: (i: number | Date) =>
                                            (lossPoints as any)[Number(i)]
                                                ?.label ?? undefined,
                                        payload: (
                                            originalPayload: Record<
                                                string,
                                                any
                                            >,
                                        ) => {
                                            const formatted: Record<
                                                string,
                                                any
                                            > = {};
                                            Object.entries(
                                                originalPayload,
                                            ).forEach(([key, value]) => {
                                                formatted[key] =
                                                    typeof value === 'number'
                                                        ? `${value.toFixed(2)}%`
                                                        : value;
                                            });
                                            return formatted;
                                        },
                                    },
                                )
                            "
                        />
                    </VisXYContainer>

                    <div
                        class="border-border mt-1 flex items-center justify-center border-t pt-2"
                    >
                        <ChartLegendContent />
                    </div>
                </ChartContainer>
            </CardContent>
        </Card>
    </div>
</template>
