<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { Activity, Gauge, Timer, TrendingDown, TrendingUp } from "@lucide/vue";
import { computed } from "vue";
import MetricsLineChart from "@/components/public/MetricsLineChart.vue";
import MetricsRangePicker from "@/components/public/MetricsRangePicker.vue";
import { useMetricsDashboardRefresh } from "@/composables/useMetricsDashboardRefresh";
import PublicLayout from "@/layouts/PublicLayout.vue";
import type {
    MetricsGranularity,
    MetricsRange,
    MetricsSeriesPoint,
    ProviderInfo,
} from "@/types/public";

const props = defineProps<{
    range: MetricsRange;
    from: string;
    to: string;
    granularity: MetricsGranularity;
    providers: ProviderInfo[];
    downloadSeries: MetricsSeriesPoint[];
    uploadSeries: MetricsSeriesPoint[];
    pingSeries: MetricsSeriesPoint[];
    latencySeries: MetricsSeriesPoint[];
    jitterSeries: MetricsSeriesPoint[];
}>();

const PROVIDER_COLORS: Record<string, string> = {
    ookla: "var(--chart-1)",
    librespeed: "var(--chart-2)",
    netflix: "var(--chart-3)",
    cloudflare: "var(--chart-4)",
};

const speedSeriesConfig = computed(() =>
    props.providers.map((p) => ({
        key: p.slug,
        label: p.label,
        color: PROVIDER_COLORS[p.slug] ?? "var(--chart-5)",
        unit: "Mbps",
    })),
);

const msSeriesConfig = computed(() =>
    props.providers.map((p) => ({
        key: p.slug,
        label: p.label,
        color: PROVIDER_COLORS[p.slug] ?? "var(--chart-5)",
        unit: "ms",
    })),
);

function navigate(range: MetricsRange, from?: string, to?: string): void {
    router.get(
        route("public.metrics"),
        range === "custom" ? { range, from, to } : { range },
        { preserveScroll: true },
    );
}

useMetricsDashboardRefresh(() => {
    router.reload();
});
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

            <!-- Download Speed -->
            <section class="flex flex-col gap-2">
                <div class="flex items-center gap-1.5">
                    <TrendingDown class="text-muted-foreground size-3.5" />
                    <p
                        class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground"
                    >
                        Download Speed
                    </p>
                </div>
                <MetricsLineChart
                    title="Download Speed per Provider"
                    description="Mbps by provider across the selected range"
                    :points="downloadSeries"
                    :series="speedSeriesConfig"
                />
            </section>

            <!-- Upload Speed -->
            <section class="flex flex-col gap-2">
                <div class="flex items-center gap-1.5">
                    <TrendingUp class="text-muted-foreground size-3.5" />
                    <p
                        class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground"
                    >
                        Upload Speed
                    </p>
                </div>
                <MetricsLineChart
                    title="Upload Speed per Provider"
                    description="Mbps by provider across the selected range"
                    :points="uploadSeries"
                    :series="speedSeriesConfig"
                />
            </section>

            <!-- Ping -->
            <section class="flex flex-col gap-2">
                <div class="flex items-center gap-1.5">
                    <Activity class="text-muted-foreground size-3.5" />
                    <p
                        class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground"
                    >
                        Ping
                    </p>
                </div>
                <MetricsLineChart
                    title="Ping per Provider"
                    description="Round-trip latency (ms) by provider across the selected range"
                    :points="pingSeries"
                    :series="msSeriesConfig"
                />
            </section>

            <!-- Latency IQM -->
            <section class="flex flex-col gap-2">
                <div class="flex items-center gap-1.5">
                    <Timer class="text-muted-foreground size-3.5" />
                    <p
                        class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground"
                    >
                        Latency (IQM)
                    </p>
                </div>
                <MetricsLineChart
                    title="Latency (IQM) per Provider"
                    description="Ping + jitter composite latency (ms) by provider"
                    :points="latencySeries"
                    :series="msSeriesConfig"
                />
            </section>

            <!-- Jitter -->
            <section class="flex flex-col gap-2">
                <div class="flex items-center gap-1.5">
                    <Activity class="text-muted-foreground size-3.5" />
                    <p
                        class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground"
                    >
                        Jitter
                    </p>
                </div>
                <MetricsLineChart
                    title="Jitter per Provider"
                    description="Jitter (ms) by provider — providers without jitter data are omitted"
                    :points="jitterSeries"
                    :series="msSeriesConfig"
                />
            </section>
        </div>
    </PublicLayout>
</template>
