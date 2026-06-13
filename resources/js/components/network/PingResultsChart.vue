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
    ChartTooltip,
    ChartTooltipContent,
    componentToString,
} from "@/components/ui/chart";
import type { ChartConfig } from "@/components/ui/chart";
import type { PingTrendPoint } from "@/types/ping";

const props = defineProps<{ trend: PingTrendPoint[] }>();

const chartConfig = {
    avg_ms: {
        label: "Avg Latency",
        color: "var(--chart-1)",
    },
    packet_loss: {
        label: "Packet Loss %",
        color: "var(--chart-5)",
    },
} satisfies ChartConfig;

const latencyPoints = computed(() =>
    props.trend.map((p, i) => ({
        i,
        value: p.avg_ms ?? 0,
        label: p.bucket.slice(11, 16),
    })),
);

const lossPoints = computed(() =>
    props.trend.map((p, i) => ({
        i,
        value: p.packet_loss ?? 0,
        label: p.bucket.slice(11, 16),
    })),
);

const xAccessor = (_d: { i: number }, i: number) => i;
const yLatency = (d: { value: number }) => d.value;
const yLoss = (d: { value: number }) => d.value;
const xTickFormat = (i: number) => latencyPoints.value[i]?.label ?? "";
const yMsFormat = (v: number) => `${v}`;
const yPctFormat = (v: number) => `${v}%`;
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-2">
        <!-- Latency chart -->
        <Card class="overflow-hidden p-0">
            <CardHeader class="border-b border-border px-4 py-3">
                <CardTitle class="text-sm font-medium">Latency Trend</CardTitle>
                <CardDescription class="text-xs"
                    >Average round-trip time (ms)</CardDescription
                >
            </CardHeader>
            <CardContent class="px-4 pb-2 pt-4">
                <ChartContainer
                    :config="chartConfig"
                    class="w-full"
                    style="height: 220px"
                >
                    <VisXYContainer :data="latencyPoints" style="height: 180px">
                        <VisLine
                            :fallback-value="0"
                            :x="xAccessor"
                            :y="yLatency"
                            :color="() => 'var(--chart-1)'"
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
                            :tick-format="yMsFormat"
                            :tick-line="false"
                            :domain-line="false"
                            :grid-line="true"
                            :num-ticks="4"
                        />
                        <ChartTooltip />
                        <ChartCrosshair
                            :color="['var(--chart-1)']"
                            :template="
                                componentToString(
                                    chartConfig,
                                    ChartTooltipContent,
                                    {
                                        indicator: 'line',
                                        labelFormatter: (i: number | Date) =>
                                            latencyPoints[Number(i)]?.label ??
                                            '',
                                        payload: (p: Record<string, any>) => {
                                            const out: Record<string, any> = {};
                                            Object.entries(p).forEach(
                                                ([k, v]) => {
                                                    out[k] =
                                                        typeof v === 'number'
                                                            ? `${v.toFixed(1)} ms`
                                                            : v;
                                                },
                                            );
                                            return out;
                                        },
                                    },
                                )
                            "
                        />
                    </VisXYContainer>
                </ChartContainer>
            </CardContent>
        </Card>

        <!-- Packet loss chart -->
        <Card class="overflow-hidden p-0">
            <CardHeader class="border-b border-border px-4 py-3">
                <CardTitle class="text-sm font-medium"
                    >Packet Loss Trend</CardTitle
                >
                <CardDescription class="text-xs"
                    >Packet loss percentage over time</CardDescription
                >
            </CardHeader>
            <CardContent class="px-4 pb-2 pt-4">
                <ChartContainer
                    :config="chartConfig"
                    class="w-full"
                    style="height: 220px"
                >
                    <VisXYContainer :data="lossPoints" style="height: 180px">
                        <VisLine
                            :fallback-value="0"
                            :x="xAccessor"
                            :y="yLoss"
                            :color="() => 'var(--chart-5)'"
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
                            :tick-format="yPctFormat"
                            :tick-line="false"
                            :domain-line="false"
                            :grid-line="true"
                            :num-ticks="4"
                        />
                        <ChartTooltip />
                        <ChartCrosshair
                            :color="['var(--chart-5)']"
                            :template="
                                componentToString(
                                    chartConfig,
                                    ChartTooltipContent,
                                    {
                                        indicator: 'line',
                                        labelFormatter: (i: number | Date) =>
                                            lossPoints[Number(i)]?.label ?? '',
                                        payload: (p: Record<string, any>) => {
                                            const out: Record<string, any> = {};
                                            Object.entries(p).forEach(
                                                ([k, v]) => {
                                                    out[k] =
                                                        typeof v === 'number'
                                                            ? `${v.toFixed(2)}%`
                                                            : v;
                                                },
                                            );
                                            return out;
                                        },
                                    },
                                )
                            "
                        />
                    </VisXYContainer>
                </ChartContainer>
            </CardContent>
        </Card>
    </div>
</template>
