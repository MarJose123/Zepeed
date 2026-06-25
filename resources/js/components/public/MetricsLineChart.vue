<script setup lang="ts">
import {
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ReferenceDot,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from "vccs";
import { computed, ref } from "vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import type { SeriesConfig } from "@/types/public";

// null = provider had no result at this timestamp; connectNulls bridges the gap
type DataPoint = Record<string, number | string | null>;

const props = defineProps<{
    title: string;
    description: string;
    points: DataPoint[];
    series: SeriesConfig[];
}>();

const hiddenKeys = ref<Set<string>>(new Set());

function toggleSeries(dataKey: string): void {
    if (hiddenKeys.value.has(dataKey)) {
        hiddenKeys.value.delete(dataKey);
    } else {
        hiddenKeys.value.add(dataKey);
    }

    hiddenKeys.value = new Set(hiddenKeys.value);
}

function formatYTick(value: number): string {
    return value >= 1000 ? `${(value / 1000).toFixed(1)}k` : String(value);
}

/**
 * Latest non-null data point per series — used to place a highlighted
 * ReferenceDot with a custom #shape slot at the most recent result.
 */
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
            <CardDescription class="text-[11px]">{{
                description
            }}</CardDescription>
        </CardHeader>
        <CardContent class="px-2 pb-3 pt-4">
            <ResponsiveContainer width="100%" :height="260">
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
                        :tick-formatter="formatYTick"
                        :width="40"
                    />

                    <Tooltip>
                        <template #content="{ active, payload, label }">
                            <div
                                v-if="active && payload && payload.length"
                                class="bg-popover border-border text-popover-foreground rounded-lg border px-3 py-2 shadow-md"
                            >
                                <p class="text-xs font-medium mb-1.5">
                                    {{ label }}
                                </p>
                                <div class="flex flex-col gap-1">
                                    <div
                                        v-for="entry in payload"
                                        :key="String(entry.dataKey)"
                                        class="flex items-center gap-2"
                                    >
                                        <span
                                            class="size-2 rounded-full shrink-0"
                                            :style="{
                                                backgroundColor: entry.color,
                                            }"
                                        />
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ entry.name }}:
                                        </span>
                                        <span class="text-xs font-medium">
                                            {{
                                                typeof entry.value === "number"
                                                    ? entry.value.toFixed(2)
                                                    : (entry.value ?? "—")
                                            }}
                                            {{
                                                typeof entry.value === "number"
                                                    ? (series.find(
                                                          (s) =>
                                                              s.key ===
                                                              String(
                                                                  entry.dataKey,
                                                              ),
                                                      )?.unit ?? "")
                                                    : ""
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Tooltip>

                    <Legend>
                        <template #content="{ payload }">
                            <div
                                class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 pt-2"
                            >
                                <button
                                    v-for="entry in payload"
                                    :key="String(entry.value)"
                                    type="button"
                                    class="flex items-center gap-1 transition-opacity"
                                    :class="{
                                        'opacity-35': hiddenKeys.has(
                                            String(entry.dataKey),
                                        ),
                                    }"
                                    @click="toggleSeries(String(entry.dataKey))"
                                >
                                    <span
                                        v-if="
                                            series.find(
                                                (s) =>
                                                    s.key ===
                                                    String(entry.dataKey),
                                            )?.dashed
                                        "
                                        class="inline-block h-0 w-4 shrink-0 border-t-2 border-dashed"
                                        :style="{ borderColor: entry.color }"
                                    />
                                    <span
                                        v-else
                                        class="size-2 rounded-full shrink-0"
                                        :style="{
                                            backgroundColor: entry.color,
                                        }"
                                    />
                                    <span
                                        class="text-[10px] text-muted-foreground"
                                    >
                                        {{ entry.value }}
                                    </span>
                                </button>
                            </div>
                        </template>
                    </Legend>

                    <!-- Multi-Line Chart: one <Line> per series -->
                    <Line
                        v-for="s in series"
                        :key="s.key"
                        :data-key="s.key"
                        :name="s.label"
                        type="monotone"
                        :stroke="s.color"
                        :stroke-width="2"
                        :stroke-dasharray="s.dashed ? '5 5' : undefined"
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

                    <!-- ReferenceDot with #shape slot: highlights the latest result per series -->
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
                                <!-- Outer glow ring -->
                                <circle
                                    :cx="cx"
                                    :cy="cy"
                                    :r="r"
                                    :fill="dot.color"
                                    fill-opacity="0.18"
                                    stroke="none"
                                />
                                <!-- Inner filled dot -->
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
