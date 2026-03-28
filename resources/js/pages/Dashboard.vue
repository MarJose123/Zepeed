<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import ProviderCard from "@/components/dashboard/ProviderCard.vue";
import SpeedChart from "@/components/dashboard/SpeedChart.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type {
    ProviderCard as IProviderCard,
    ChartData,
} from "@/types/dashboard";

defineProps<{
    providerCards: IProviderCard[];
    chartData: ChartData;
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Dashboard", href: route("dashboard", {}, false) },
];
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex flex-col gap-1 py-5">
                <h1 class="text-xl font-semibold">Dashboard</h1>
                <p class="text-muted-foreground text-sm">
                    Real-time overview of your internet speed across all
                    providers
                </p>
            </div>

            <!-- Provider cards -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <ProviderCard
                    v-for="provider in providerCards"
                    :key="provider.slug"
                    :provider="provider"
                />
            </div>

            <!-- Speed comparison charts -->
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
