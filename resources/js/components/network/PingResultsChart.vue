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

// ── Latency ──────────────────────────────────────────────────────────────────

const latencyConfig = {
    avg_ms: { label: "Avg Latency", color: "var(--chart-1)" },
} satisfies ChartConfig;

const latencyColor = "var(--chart-1)";

type LatencyPoint = { i: number; label: string; avg_ms: number };

const latencyPoints = computed<LatencyPoint[]>(() =>
    props.trend.map((p, i) => ({
        i,
        label: p.bucket.slice(11, 16),
        avg_ms: p.avg_ms ?? 0,
    })),
);

const latencyX = (_d: LatencyPoint, i: number) => i;
const latencyY = [(d: LatencyPoint) => d.avg_ms];
const latencyColorAccessor = (_d: LatencyPoint, _i: number) => latencyColor;
const latencyXFormat = (i: number) => latencyPoints.value[i]?.label ?? "";
const latencyYFormat = (v: number) => `${v}`;

// ── Packet Loss ───────────────────────────────────────────────────────────────

const lossConfig = {
    packet_loss: { label: "Packet Loss", color: "var(--chart-5)" },
} satisfies ChartConfig;

const lossColor = "var(--chart-5)";

type LossPoint = { i: number; label: string; packet_loss: number };

const lossPoints = computed<LossPoint[]>(() =>
    props.trend.map((p, i) => ({
        i,
        label: p.bucket.slice(11, 16),
        packet_loss: p.packet_loss ?? 0,
    })),
);

const lossX = (_d: LossPoint, i: number) => i;
const lossY = [(d: LossPoint) => d.packet_loss];
const lossColorAccessor = (_d: LossPoint, _i: number) => lossColor;
const lossXFormat = (i: number) => lossPoints.value[i]?.label ?? "";
const lossYFormat = (v: number) => `${v}%`;
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-2">
        <!-- Latency Trend -->
        <Card class="overflow-hidden p-0">
            <CardHeader class="border-border border-b px-4 py-3">
                <CardTitle class="text-sm font-medium">Latency Trend</CardTitle>
                <CardDescription class="text-[11px]">
                    Average round-trip time (ms)
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
                            :x="latencyX"
                            :y="latencyY"
                            :color="latencyColorAccessor"
                            curve-type="basis"
                        />
                        <VisAxis
                            type="x"
                            :x="latencyX"
                            :tick-format="latencyXFormat"
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
                            :color="[latencyColor]"
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
                    Packet loss percentage over time
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
                            :x="lossX"
                            :y="lossY"
                            :color="lossColorAccessor"
                            curve-type="basis"
                        />
                        <VisAxis
                            type="x"
                            :x="lossX"
                            :tick-format="lossXFormat"
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
                            :color="[lossColor]"
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
                </ChartContainer>
            </CardContent>
        </Card>
    </div>
</template>
