<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { Activity, Gauge, Network, Timer, Zap } from "@lucide/vue";
import { computed } from "vue";
import MetricsLineChart from "@/components/public/MetricsLineChart.vue";
import MetricsRangePicker from "@/components/public/MetricsRangePicker.vue";
import { useMetricsDashboardRefresh } from "@/composables/useMetricsDashboardRefresh";
import PublicLayout from "@/layouts/PublicLayout.vue";
import type {
    MetricsGranularity,
    MetricsRange,
    MetricsSeriesPoint,
    PingTargetInfo,
    ProviderInfo,
} from "@/types/public";

const props = defineProps<{
    range: MetricsRange;
    from: string;
    to: string;
    granularity: MetricsGranularity;
    providers: ProviderInfo[];
    pingTargets: PingTargetInfo[];
    speedSeries: MetricsSeriesPoint[];
    pingSeries: MetricsSeriesPoint[];
    latencySeries: MetricsSeriesPoint[];
    jitterSeries: MetricsSeriesPoint[];
    networkPingSeries: MetricsSeriesPoint[];
}>();

const PROVIDER_COLORS: Record<string, string> = {
    ookla: "#3b82f6", // blue-500    — clearly blue
    librespeed: "#22c55e", // green-500   — clearly green
    netflix: "#ef4444", // red-500     — clearly red
    cloudflare: "#f97316", // orange-500  — clearly orange
};

const CHART_COLORS = [
    "#3b82f6", // blue
    "#22c55e", // green
    "#f97316", // orange
    "#a855f7", // purple
    "#14b8a6", // teal
    "#ef4444", // red
    "#f59e0b", // amber
    "#ec4899", // pink
];

/** Download (solid) + Upload (dashed) per provider — one combined speed chart. */
const speedSeriesConfig = computed(() => {
    const configs = [];

    for (const p of props.providers) {
        const color = PROVIDER_COLORS[p.slug] ?? "var(--chart-5)";
        configs.push({
            key: `${p.slug}_dl`,
            label: `${p.label} ↓`,
            color,
            unit: "Mbps",
            dashed: false,
        });
        configs.push({
            key: `${p.slug}_ul`,
            label: `${p.label} ↑`,
            color,
            unit: "Mbps",
            dashed: true,
        });
    }

    return configs;
});

/** Ping / Latency IQM / Jitter — one line per provider. */
const msSeriesConfig = computed(() =>
    props.providers.map((p) => ({
        key: p.slug,
        label: p.label,
        color: PROVIDER_COLORS[p.slug] ?? "var(--chart-5)",
        unit: "ms",
        dashed: false as const,
    })),
);

/** Network ping — one line per ping target. */
const networkPingSeriesConfig = computed(() =>
    props.pingTargets.map((t, i) => ({
        key: t.id,
        label: t.label,
        color: CHART_COLORS[i % CHART_COLORS.length] ?? "var(--chart-1)",
        unit: "ms",
        dashed: false as const,
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

            <!-- Speed (Download ↓ + Upload ↑ per provider, one chart) -->
            <section class="flex flex-col gap-2">
                <div class="flex items-center gap-1.5">
                    <Zap class="text-muted-foreground size-3.5" />
                    <p
                        class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground"
                    >
                        Speed
                    </p>
                </div>
                <MetricsLineChart
                    title="Download & Upload Speed per Provider"
                    description="Solid line = download ↓ · Dashed line = upload ↑ · Mbps"
                    :points="speedSeries"
                    :series="speedSeriesConfig"
                />
            </section>

            <!-- Speedtest Ping -->
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
                    title="Speedtest Ping per Provider"
                    description="Round-trip latency (ms) reported by each speedtest provider"
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
                    description="Composite latency — ping + jitter (ms) per provider"
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
                    description="Jitter (ms) per provider — providers without jitter data are omitted"
                    :points="jitterSeries"
                    :series="msSeriesConfig"
                />
            </section>

            <!-- Network Ping (ping_results) -->
            <section v-if="pingTargets.length > 0" class="flex flex-col gap-2">
                <div class="flex items-center gap-1.5">
                    <Network class="text-muted-foreground size-3.5" />
                    <p
                        class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground"
                    >
                        Network Ping
                    </p>
                </div>
                <MetricsLineChart
                    title="Network Ping per Target"
                    description="Average round-trip latency (ms) to each configured ping target"
                    :points="networkPingSeries"
                    :series="networkPingSeriesConfig"
                />
            </section>
        </div>
    </PublicLayout>
</template>
