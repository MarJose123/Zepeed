<script setup lang="ts">
import {
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ReferenceDot,
    ReferenceLine,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from "vccs";
import { computed, ref } from "vue";
import DashboardSpeedChartLegend from "@/components/dashboard/DashboardSpeedChartLegend.vue";
import DashboardSpeedChartTooltip from "@/components/dashboard/DashboardSpeedChartTooltip.vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import type {
    DashboardSeriesConfig,
    DashboardSeriesPoint,
} from "@/types/dashboard";

const props = defineProps<{
    title: string;
    description: string;
    series: DashboardSeriesConfig[];
    points: DashboardSeriesPoint[];
    averages: Record<string, number>;
}>();

const hiddenKeys = ref<Set<string>>(new Set());

function toggleSeries(key: string): void {
    hiddenKeys.value = new Set(
        hiddenKeys.value.has(key)
            ? [...hiddenKeys.value].filter((k) => k !== key)
            : [...hiddenKeys.value, key],
    );
}

function formatY(v: number): string {
    return v >= 1000 ? `${(v / 1000).toFixed(1)}k` : String(v);
}

/** Mean of per-series averages — y-value for the reference line. */
const overallAvg = computed<number>(() => {
    const vals = props.series
        .map((s) => props.averages[s.key] ?? 0)
        .filter((v) => v > 0);

    if (!vals.length) return 0;

    return (
        Math.round((vals.reduce((a, b) => a + b, 0) / vals.length) * 100) / 100
    );
});

/** Last non-null data point per series — anchor for the custom ReferenceDot. */
const latestPoints = computed(() =>
    props.series.flatMap((s) => {
        for (let i = props.points.length - 1; i >= 0; i--) {
            const val = props.points[i]?.[s.key];

            if (typeof val === "number") {
                return [
                    {
                        seriesKey: s.key,
                        label: String(props.points[i]?.label ?? ""),
                        value: val,
                        color: s.color,
                    },
                ];
            }
        }

        return [];
    }),
);
</script>

<template>
    <Card class="overflow-hidden p-0">
        <CardHeader class="border-border border-b px-4 py-3">
            <CardTitle class="text-sm font-medium">{{ title }}</CardTitle>
            <CardDescription class="mt-0.5 text-[11px]">{{
                description
            }}</CardDescription>
        </CardHeader>

        <CardContent class="px-2 pb-3 pt-4">
            <div
                v-if="!points.length || !series.length"
                class="flex h-[260px] items-center justify-center"
            >
                <p class="text-xs text-muted-foreground">
                    No data available for the selected range.
                </p>
            </div>

            <ResponsiveContainer v-else width="100%" :height="260">
                <LineChart
                    :data="points"
                    :margin="{ top: 8, right: 16, bottom: 0, left: 0 }"
                >
                    <CartesianGrid
                        stroke-dasharray="3 3"
                        stroke="var(--border)"
                        :stroke-opacity="0.5"
                        :vertical="false"
                    />
                    <XAxis
                        data-key="label"
                        :tick-line="false"
                        :axis-line="false"
                    />
                    <YAxis
                        :tick-line="false"
                        :axis-line="false"
                        :tick-formatter="formatY"
                        :width="40"
                    />

                    <Tooltip>
                        <template #content="{ active, payload, label }">
                            <DashboardSpeedChartTooltip
                                :active="active"
                                :payload="payload"
                                :label="label"
                            />
                        </template>
                    </Tooltip>

                    <Legend>
                        <template #content="{ payload }">
                            <DashboardSpeedChartLegend
                                :payload="payload"
                                :hidden-keys="hiddenKeys"
                                @toggle="toggleSeries"
                            />
                        </template>
                    </Legend>

                    <!-- Dashed average reference line with inline label -->
                    <ReferenceLine
                        v-if="overallAvg > 0"
                        :y="overallAvg"
                        stroke="var(--muted-foreground)"
                        stroke-dasharray="4 4"
                        :stroke-width="1"
                        :label="{
                            value: `Avg: ${overallAvg.toFixed(1)} Mbps`,
                            position: 'insideTopRight',
                            fontSize: 10,
                            fill: 'var(--muted-foreground)',
                        }"
                        if-overflow="visible"
                    />

                    <!-- One solid line per provider -->
                    <Line
                        v-for="s in series"
                        :key="s.key"
                        :data-key="s.key"
                        :name="s.label"
                        type="monotone"
                        :stroke="s.color"
                        :stroke-width="2"
                        :dot="{
                            r: 3,
                            fill: s.color,
                            stroke: 'var(--card)',
                            strokeWidth: 2,
                        }"
                        :active-dot="false"
                        :connect-nulls="true"
                        :hide="hiddenKeys.has(s.key)"
                    />

                    <!-- Glow-ring dot pinned to the most recent result per provider -->
                    <ReferenceDot
                        v-for="dot in latestPoints"
                        :key="dot.seriesKey"
                        :x="dot.label"
                        :y="dot.value"
                        :r="7"
                        :fill="dot.color"
                        :stroke="dot.color"
                        if-overflow="visible"
                    >
                        <template #shape="{ cx, cy, r }">
                            <g>
                                <circle
                                    :cx="cx"
                                    :cy="cy"
                                    :r="r"
                                    :fill="dot.color"
                                    fill-opacity="0.18"
                                    stroke="none"
                                />
                                <circle
                                    :cx="cx"
                                    :cy="cy"
                                    :r="r * 0.52"
                                    :fill="dot.color"
                                    stroke="var(--card)"
                                    stroke-width="2"
                                />
                            </g>
                        </template>
                    </ReferenceDot>
                </LineChart>
            </ResponsiveContainer>
        </CardContent>
    </Card>
</template>
