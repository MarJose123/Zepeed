<script setup lang="ts">
import { Head, router, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import ProviderCard from "@/components/dashboard/ProviderCard.vue";
import RecentPingResultsTable from "@/components/dashboard/RecentPingResultsTable.vue";
import SpeedChart from "@/components/dashboard/SpeedChart.vue";
import { useDashboardRefresh } from "@/composables/useDashboardRefresh";
import { usePingResultsChannel } from "@/composables/usePingResultsChannel";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type {
    ChartData,
    ProviderCard as IProviderCard,
    RecentPingResult,
} from "@/types/dashboard";

const props = defineProps<{
    providerCards: IProviderCard[];
    chartData: ChartData;
    recentPingResults: RecentPingResult[];
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Dashboard", href: route("dashboard", {}, false) },
];

const page = usePage<{
    providerCards: IProviderCard[];
    chartData: ChartData;
    recentPingResults: RecentPingResult[];
}>();

const providerCards = ref<IProviderCard[]>(props.providerCards);
const chartData = ref<ChartData>(props.chartData);
const recentPingResults = ref<RecentPingResult[]>(props.recentPingResults);

// Reload speed data on speedtest completion
useDashboardRefresh(() => {
    router.reload({
        only: ["providerCards", "chartData"],
        onSuccess: () => {
            providerCards.value = page.props.providerCards;
            chartData.value = page.props.chartData;
        },
    });
});

// Reload ping table on ping completion
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
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex flex-col gap-1 py-5">
                <h1 class="text-xl font-bold tracking-tight">Dashboard</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Real-time overview of your internet speed across all
                    providers
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <ProviderCard
                    v-for="provider in providerCards"
                    :key="provider.slug"
                    :provider="provider"
                />
            </div>

            <SpeedChart
                title="Download speed comparison"
                metric="download"
                :chart-data="chartData"
            />

            <SpeedChart
                title="Upload speed comparison"
                metric="upload"
                :chart-data="chartData"
            />

            <div>
                <p
                    class="mb-2 text-[11px] font-medium uppercase tracking-wider text-muted-foreground"
                >
                    Recent ping results
                </p>
                <RecentPingResultsTable :results="recentPingResults" />
            </div>
        </div>
    </AppLayout>
</template>
