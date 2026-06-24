<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { Activity, Gauge, Timer, TrendingDown } from "@lucide/vue";
import MetricsLineChart from "@/components/public/MetricsLineChart.vue";
import MetricsRangePicker from "@/components/public/MetricsRangePicker.vue";
import { useMetricsDashboardRefresh } from "@/composables/useMetricsDashboardRefresh";
import PublicLayout from "@/layouts/PublicLayout.vue";
import type {
    JitterSeriesPoint,
    LatencySeriesPoint,
    MetricsGranularity,
    MetricsRange,
    MetricsRefreshPayload,
    SpeedSeriesPoint,
} from "@/types/public";

defineProps<{
    range: MetricsRange;
    from: string;
    to: string;
    granularity: MetricsGranularity;
    speedSeries: SpeedSeriesPoint[];
    latencySeries: LatencySeriesPoint[];
    jitterSeries: JitterSeriesPoint[];
}>();

function navigate(range: MetricsRange, from?: string, to?: string): void {
    router.get(
        route("public.metrics"),
        range === "custom" ? { range, from, to } : { range },
        { preserveScroll: true },
    );
}

useMetricsDashboardRefresh((_: MetricsRefreshPayload) => {
    router.reload();
});

const SPEED_SERIES = [
    { key: "download", label: "Download", color: "var(--chart-1)", unit: "Mbps" },
    { key: "upload",   label: "Upload",   color: "var(--chart-2)", unit: "Mbps" },
];

const PING_SERIES = [
    { key: "ping", label: "Ping", color: "#a78bfa", unit: "ms" },
];

const LATENCY_SERIES = [
    { key: "download_latency", label: "Download Latency", color: "var(--chart-1)", unit: "ms" },
    { key: "upload_latency",   label: "Upload Latency",   color: "var(--chart-2)", unit: "ms" },
];

const JITTER_SERIES = [
    { key: "download_jitter", label: "Download Jitter", color: "var(--chart-1)", unit: "ms" },
    { key: "upload_jitter",   label: "Upload Jitter",   color: "var(--chart-2)", unit: "ms" },
    { key: "ping_jitter",     label: "Ping Jitter",     color: "#a78bfa",        unit: "ms" },
];
</script>

<template>
    <Head title="Metrics Dashboard" />
    <PublicLayout>
        <div class="flex flex-1 flex-col gap-4 pt-0">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Gauge class="text-muted-foreground size-5" />
                    <h1 class="text-xl font-bold tracking-tight">
                        Metrics Dashboard
                    </h1>
                </div>
                <MetricsRangePicker
                    :range="range"
                    :from="from"
                    :to="to"
                    @change="navigate"
                />
            </div>

            <!-- Speed -->
            <section class="flex flex-col gap-2">
                <div class="flex items-center gap-1.5">
                    <TrendingDown class="text-muted-foreground size-3.5" />
                    <p class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground">
                        Speed
                    </p>
                </div>
                <MetricsLineChart
                    title="Download & Upload Speed"
                    description="Average Mbps across all providers"
                    :points="speedSeries"
                    :series="SPEED_SERIES"
                />
            </section>

            <!-- Ping -->
            <section class="flex flex-col gap-2">
                <div class="flex items-center gap-1.5">
                    <Activity class="text-muted-foreground size-3.5" />
                    <p class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground">
                        Ping
                    </p>
                </div>
                <MetricsLineChart
                    title="Ping Latency"
                    description="Average round-trip latency (ms) across all providers"
                    :points="latencySeries"
                    :series="PING_SERIES"
                />
            </section>

            <!-- Latency IQM -->
            <section class="flex flex-col gap-2">
                <div class="flex items-center gap-1.5">
                    <Timer class="text-muted-foreground size-3.5" />
                    <p class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground">
                        Latency (IQM)
                    </p>
                </div>
                <MetricsLineChart
                    title="Download & Upload Latency (IQM)"
                    description="Inter-quartile mean latency by direction"
                    :points="latencySeries"
                    :series="LATENCY_SERIES"
                />
            </section>

            <!-- Jitter -->
            <section class="flex flex-col gap-2">
                <div class="flex items-center gap-1.5">
                    <Activity class="text-muted-foreground size-3.5" />
                    <p class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground">
                        Jitter
                    </p>
                </div>
                <MetricsLineChart
                    title="Download, Upload & Ping Jitter"
                    description="Average jitter (ms) across all providers"
                    :points="jitterSeries"
                    :series="JITTER_SERIES"
                />
            </section>

        </div>
    </PublicLayout>
</template>
