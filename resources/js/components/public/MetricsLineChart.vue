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

interface SeriesConfig {
    key: string;
    label: string;
    color: string;
    unit: string;
}

// null = no data for that provider in this bucket (connectNulls handles it)
type DataPoint = Record<string, number | string | null>;

defineProps<{
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
</script>

<template>
    <Card class="overflow-hidden p-0">
        <CardHeader class="border-border border-b px-4 py-3">
            <CardTitle class="text-sm font-medium">{{ title }}</CardTitle>
            <CardDescription class="text-[11px]">
                {{ description }}
            </CardDescription>
        </CardHeader>
        <CardContent class="px-2 pb-3 pt-4">
            <ResponsiveContainer width="100%" :height="260">
                <LineChart
                    :data="points"
                    :margin="{ top: 4, right: 16, bottom: 0, left: 0 }"
                >
                    <!-- Use SVG stroke attr, not Tailwind class — CartesianGrid renders SVG -->
                    <CartesianGrid
                        stroke-dasharray="3 3"
                        stroke="var(--border)"
                        :stroke-opacity="0.5"
                        :vertical="false"
                    />
                    <!-- tick + interval props removed — not in vccs API -->
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
                    <!-- onClick removed from Legend — clicks owned by slot buttons only -->
                    <Legend>
                        <template #content="{ payload }">
                            <div
                                class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 pt-2"
                            >
                                <button
                                    v-for="entry in payload"
                                    :key="String(entry.value)"
                                    type="button"
                                    class="flex items-center gap-1.5 transition-opacity"
                                    :class="{
                                        'opacity-35': hiddenKeys.has(
                                            String(entry.dataKey),
                                        ),
                                    }"
                                    @click="toggleSeries(String(entry.dataKey))"
                                >
                                    <span
                                        class="size-2.5 rounded-full shrink-0"
                                        :style="{
                                            backgroundColor: entry.color,
                                        }"
                                    />
                                    <span
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        {{ entry.value }}
                                    </span>
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
                        :dot="false"
                        :active-dot="{ r: 4, strokeWidth: 0 }"
                        :connect-nulls="true"
                        :hide="hiddenKeys.has(s.key)"
                    />
                </LineChart>
            </ResponsiveContainer>
        </CardContent>
    </Card>
</template>
