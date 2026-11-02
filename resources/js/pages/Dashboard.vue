<script setup lang="ts">
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import DashboardRangePicker from "@/components/dashboard/DashboardRangePicker.vue";
import DashboardSpeedChart from "@/components/dashboard/DashboardSpeedChart.vue";
import ProviderCard from "@/components/dashboard/ProviderCard.vue";
import RecentPingResultsTable from "@/components/dashboard/RecentPingResultsTable.vue";
import { useAppearance } from "@/composables/useAppearance";
import { useDashboardRefresh } from "@/composables/useDashboardRefresh";
import { usePingResultsChannel } from "@/composables/usePingResultsChannel";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type {
    DashboardChartRange,
    DashboardProviderInfo,
    DashboardSeriesConfig,
    DashboardSeriesPoint,
    ProviderCard as IProviderCard,
    RecentPingResult,
} from "@/types/dashboard";

const PROVIDER_COLORS: Record<string, string> = {
    ookla: "#3b82f6",
    librespeed: "#22c55e",
    netflix: "#ef4444",
    cloudflare: "#f97316",
};

const { seasonalTheme } = useAppearance();

const seasonalGreeting = computed(() => {
    switch (seasonalTheme.value) {
        case "halloween":
            return "🎃 Spooky season — may your speeds be frightfully fast!";
        case "christmas":
            return "🎄 Happy Holidays — wishing you merry speeds!";
        case "newyear":
            return "🎉 <span class='sparkle-text'>Happy New Year — here's to faster speeds ahead!</span>";
        default:
            return null;
    }
});

const props = defineProps<{
    providerCards: IProviderCard[];
    chartSeries: DashboardSeriesPoint[];
    chartAverages: Record<string, number>;
    chartProviders: DashboardProviderInfo[];
    range: DashboardChartRange;
    from: string;
    to: string;
    recentPingResults: RecentPingResult[];
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Dashboard", href: route("dashboard", {}, false) },
];

const page = usePage<{
    providerCards: IProviderCard[];
    chartSeries: DashboardSeriesPoint[];
    chartAverages: Record<string, number>;
    chartProviders: DashboardProviderInfo[];
    recentPingResults: RecentPingResult[];
}>();

const providerCards = ref<IProviderCard[]>(props.providerCards);
const chartSeries = ref<DashboardSeriesPoint[]>(props.chartSeries);
const chartAverages = ref<Record<string, number>>(props.chartAverages);
const chartProviders = ref<DashboardProviderInfo[]>(props.chartProviders);
const recentPingResults = ref<RecentPingResult[]>(props.recentPingResults);

const downloadSeries = computed<DashboardSeriesConfig[]>(() =>
    chartProviders.value.map((p) => ({
        key: `${p.slug}_dl`,
        label: p.label,
        color: PROVIDER_COLORS[p.slug] ?? "var(--chart-1)",
        unit: "Mbps",
        dashed: false,
    })),
);

const uploadSeries = computed<DashboardSeriesConfig[]>(() =>
    chartProviders.value.map((p) => ({
        key: `${p.slug}_ul`,
        label: p.label,
        color: PROVIDER_COLORS[p.slug] ?? "var(--chart-1)",
        unit: "Mbps",
        dashed: false,
    })),
);

function navigate(r: DashboardChartRange, from?: string, to?: string): void {
    router.get(
        route("dashboard"),
        r === "custom" ? { range: r, from, to } : { range: r },
        { preserveScroll: true },
    );
}

useDashboardRefresh(() => {
    router.reload({
        only: [
            "providerCards",
            "chartSeries",
            "chartAverages",
            "chartProviders",
        ],
        onSuccess: () => {
            providerCards.value = page.props.providerCards;
            chartSeries.value = page.props.chartSeries;
            chartAverages.value = page.props.chartAverages;
            chartProviders.value = page.props.chartProviders;
        },
    });
});

usePingResultsChannel({
    onCompleted() {
        router.reload({
            only: ["recentPingResults"],
            onSuccess: () => {
                recentPingResults.value = page.props.recentPingResults;
            },
        });
    },
});
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6 pt-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Dashboard</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Real-time overview of your internet speed across all
                    providers
                </p>
                <p
                    v-if="seasonalGreeting"
                    class="mt-2 text-sm font-medium"
                    v-html="seasonalGreeting"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <ProviderCard
                    v-for="provider in providerCards"
                    :key="provider.slug"
                    :provider="provider"
                />
            </div>

            <div class="flex items-center justify-end">
                <DashboardRangePicker
                    :range="range"
                    :from="from"
                    :to="to"
                    @change="navigate"
                />
            </div>

            <DashboardSpeedChart
                title="Download speed"
                description="Download throughput (Mbps) per provider — dashed line marks the period average"
                :series="downloadSeries"
                :points="chartSeries"
                :averages="chartAverages"
            />

            <DashboardSpeedChart
                title="Upload speed"
                description="Upload throughput (Mbps) per provider — dashed line marks the period average"
                :series="uploadSeries"
                :points="chartSeries"
                :averages="chartAverages"
            />

            <div>
                <p
                    class="mb-3 text-xs font-medium uppercase tracking-wider text-muted-foreground"
                >
                    Recent ping results
                </p>
                <RecentPingResultsTable :results="recentPingResults" />
            </div>
        </div>
    </AppLayout>
</template>
