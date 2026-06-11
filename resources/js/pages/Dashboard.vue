<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";
import ProviderCard from "@/components/dashboard/ProviderCard.vue";
import SpeedChart from "@/components/dashboard/SpeedChart.vue";
import { useDashboardRefresh } from "@/composables/useDashboardRefresh";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type {
    ProviderCard as IProviderCard,
    ChartData,
} from "@/types/dashboard";

const props = defineProps<{
    providerCards: IProviderCard[];
    chartData: ChartData;
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Dashboard", href: route("dashboard", {}, false) },
];

const page = usePage<{
    providerCards: IProviderCard[];
    chartData: ChartData;
}>();

const providerCards = ref<IProviderCard[]>(props.providerCards);
const chartData = ref<ChartData>(props.chartData);

useDashboardRefresh(() => {
    router.reload({
        only: ["providerCards", "chartData"],
        onSuccess: () => {
            providerCards.value = page.props.providerCards;
            chartData.value = page.props.chartData;
        },
    });
});
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex flex-col gap-1 py-5">
                <h1 class="text-xl font-bold tracking-tight">Dashboard</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Real-time overview of your internet speed across all
                    providers
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
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
        </div>
    </AppLayout>
</template>
