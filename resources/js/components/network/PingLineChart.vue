<script setup lang="ts">
import {
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from "vccs";
import { ref } from "vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import type { PingSeriesConfig } from "@/types/ping";

type DataPoint = { label: string; [key: string]: number | string };

defineProps<{
    title: string;
    description: string;
    series: PingSeriesConfig[];
    points: DataPoint[];
    tickFormatter: (value: number) => string;
}>();

const hiddenKeys = ref<Set<string>>(new Set());

function toggleSeries(key: string): void {
    hiddenKeys.value = new Set(
        hiddenKeys.value.has(key)
            ? [...hiddenKeys.value].filter((k) => k !== key)
            : [...hiddenKeys.value, key],
    );
}
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
            <ResponsiveContainer width="100%" :height="220">
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
                        :tick-formatter="tickFormatter"
                        :width="44"
                    />

                    <Tooltip>
                        <template
                            #content="{ active, payload, label: bucketLabel }"
                        >
                            <div
                                v-if="active && payload?.length"
                                class="bg-popover border-border text-popover-foreground rounded-lg border px-3 py-2 shadow-md"
                            >
                                <p class="mb-1.5 text-xs font-medium">
                                    {{ bucketLabel }}
                                </p>
                                <div class="flex flex-col gap-1">
                                    <div
                                        v-for="e in payload"
                                        :key="String(e.dataKey)"
                                        class="flex items-center gap-2"
                                    >
                                        <span
                                            class="size-2 shrink-0 rounded-full"
                                            :style="{
                                                backgroundColor: e.color,
                                            }"
                                        />
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >{{ e.name }}:</span
                                        >
                                        <span class="text-xs font-medium">{{
                                            typeof e.value === "number"
                                                ? tickFormatter(e.value)
                                                : "—"
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Tooltip>

                    <Legend>
                        <template #content="{ payload: legendPayload }">
                            <div
                                class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 pt-2"
                            >
                                <button
                                    v-for="e in legendPayload"
                                    :key="String(e.value)"
                                    type="button"
                                    class="flex items-center gap-1 transition-opacity"
                                    :class="{
                                        'opacity-35': hiddenKeys.has(
                                            String(e.dataKey),
                                        ),
                                    }"
                                    @click="toggleSeries(String(e.dataKey))"
                                >
                                    <span
                                        class="size-2 shrink-0 rounded-full"
                                        :style="{ backgroundColor: e.color }"
                                    />
                                    <span
                                        class="text-[10px] text-muted-foreground"
                                        >{{ e.value }}</span
                                    >
                                </button>
                            </div>
                        </template>
                    </Legend>

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
                </LineChart>
            </ResponsiveContainer>
        </CardContent>
    </Card>
</template>
