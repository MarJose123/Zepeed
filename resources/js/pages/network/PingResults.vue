<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import PingResultsChart from "@/components/network/PingResultsChart.vue";
import PingResultsStatCards from "@/components/network/PingResultsStatCards.vue";
import PingResultsTable from "@/components/network/PingResultsTable.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type {
    PingResult,
    PingResultFilters,
    PingResultPagination,
    PingResultStats,
    PingTarget,
    PingTrendBucket,
} from "@/types/ping";

defineProps<{
    results: PingResult[];
    pagination: PingResultPagination;
    stats: PingResultStats;
    trend: PingTrendBucket[];
    targets: PingTarget[];
    filters: PingResultFilters;
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Network", href: "#" },
    {
        title: "Ping Results",
        href: route("speedtest.network.ping-results.index", {}, false),
    },
];
</script>

<template>
    <Head title="Ping Results" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-bold tracking-tight">
                        Ping Results
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Historical data, trends, and network performance
                        metrics.
                    </p>
                </div>
            </div>

            <PingResultsStatCards :stats="stats" />

            <PingResultsChart :trend="trend" :targets="targets" />

            <PingResultsTable
                :results="results"
                :pagination="pagination"
                :targets="targets"
                :filters="filters"
            />
        </div>
    </AppLayout>
</template>
