<script setup lang="ts">
import { VisAxis, VisLine, VisXYContainer } from "@unovis/vue";
import { computed, ref } from "vue";
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import type { ChartData } from "@/types/dashboard";

const props = defineProps<{
    title: string;
    metric: "download" | "upload";
    chartData: ChartData;
}>();

type Range = "24h" | "7d" | "30d";

const selectedRange = ref<Range>("24h");

const rangeLabels: Record<Range, string> = {
    "24h": "Last 24 hours",
    "7d": "Last 7 days",
    "30d": "Last 30 days",
};

const subtitle = computed(
    () =>
        `Showing all providers for the ${rangeLabels[selectedRange.value].toLowerCase()}`,
);

// Use CSS chart variables — SVG is part of the DOM so they resolve correctly
const providerColors = {
    speedtest: "var(--chart-1)",
    librespeed: "var(--chart-3)",
    "netflix-speedtest": "var(--chart-5)",
};

const chartConfig = {
    speedtest: {
        label: "Ookla Speedtest",
        color: providerColors.speedtest,
    },
    librespeed: {
        label: "LibreSpeed",
        color: providerColors.librespeed,
    },
    "netflix-speedtest": {
        label: "Netflix Speedtest",
        color: providerColors["netflix-speedtest"],
    },
} satisfies ChartConfig;

const chartPoints = computed(() => {
    const rangeData = props.chartData[selectedRange.value];

    return rangeData.labels.map((label, i) => ({
        label,
        speedtest: rangeData.datasets["speedtest"]?.[props.metric]?.[i] ?? 0,
        librespeed: rangeData.datasets["librespeed"]?.[props.metric]?.[i] ?? 0,
        "netflix-speedtest":
            rangeData.datasets["netflix-speedtest"]?.[props.metric]?.[i] ?? 0,
    }));
});

type DataPoint = (typeof chartPoints.value)[number];

const xAccessor = (_d: DataPoint, i: number) => i;

const yAccessors = [
    (d: DataPoint) => d.speedtest,
    (d: DataPoint) => d.librespeed,
    (d: DataPoint) => d["netflix-speedtest"],
];

const colorAccessor = (_d: DataPoint, i: number) =>
    [
        providerColors.speedtest,
        providerColors.librespeed,
        providerColors["netflix-speedtest"],
    ][i] ?? "var(--chart-1)";

const colors = [
    providerColors.speedtest,
    providerColors.librespeed,
    providerColors["netflix-speedtest"],
];

const xTickFormat = (i: number) => chartPoints.value[i]?.label ?? "";

// Y axis — show Mbps unit
const yTickFormat = (v: number) => `${v.toLocaleString()}`;
</script>

<template>
    <Card class="overflow-hidden p-0">
        <CardHeader class="border-border border-b px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <CardTitle class="text-sm font-medium">{{
                        title
                    }}</CardTitle>
                    <CardDescription class="mt-0.5 text-xs">{{
                        subtitle
                    }}</CardDescription>
                </div>
                <Select v-model="selectedRange">
                    <SelectTrigger class="h-8 w-auto min-w-[130px] text-xs">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent align="end">
                        <SelectItem value="24h" class="text-xs"
                            >Last 24 hours</SelectItem
                        >
                        <SelectItem value="7d" class="text-xs"
                            >Last 7 days</SelectItem
                        >
                        <SelectItem value="30d" class="text-xs"
                            >Last 30 days</SelectItem
                        >
                    </SelectContent>
                </Select>
            </div>
        </CardHeader>

        <CardContent class="px-4 pb-2 pt-4">
            <ChartContainer
                :config="chartConfig"
                class="w-full"
                style="height: 270px"
                :cursor="false"
            >
                <VisXYContainer :data="chartPoints" style="height: 210px">
                    <VisLine
                        :fallbackValue="0"
                        :x="xAccessor"
                        :y="yAccessors"
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
                        :tick-format="yTickFormat"
                        :tick-line="false"
                        :domain-line="false"
                        :grid-line="true"
                        :num-ticks="4"
                    />
                    <ChartTooltip />
                    <ChartCrosshair
                        :template="
                            componentToString(
                                chartConfig,
                                ChartTooltipContent,
                                {
                                    indicator: 'line',
                                    labelFormatter: (i: number | Date) => {
                                        return (
                                            (chartPoints as any)[Number(i)]
                                                ?.label ?? undefined
                                        );
                                    },
                                    payload: (
                                        originalPayload: Record<string, any>,
                                    ) => {
                                        const formatted: Record<string, any> =
                                            {};
                                        Object.entries(originalPayload).forEach(
                                            ([key, value]) => {
                                                formatted[key] =
                                                    typeof value === 'number'
                                                        ? ` ${value.toLocaleString()} Mbps`
                                                        : value;
                                            },
                                        );
                                        return formatted;
                                    },
                                },
                            )
                        "
                        :color="colors"
                    />
                </VisXYContainer>

                <!-- Legend centered at bottom -->
                <div
                    class="border-border mt-1 flex items-center justify-center border-t pt-2"
                >
                    <ChartLegendContent />
                </div>
            </ChartContainer>
        </CardContent>
    </Card>
</template>
