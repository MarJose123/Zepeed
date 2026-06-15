<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";
import PingResultsChart from "@/components/network/PingResultsChart.vue";
import PingResultsStatCards from "@/components/network/PingResultsStatCards.vue";
import PingResultsTable from "@/components/network/PingResultsTable.vue";
import { usePingResultsChannel } from "@/composables/usePingResultsChannel";
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

const lastUpdated = ref<string | null>(null);

// Real-time: reload Inertia page data whenever a ping result completes
usePingResultsChannel({
    onCompleted(payload) {
        lastUpdated.value = payload.measured_at;
        router.reload({
            only: ["results", "stats", "trend"],
        });
    },
});
</script>

<template>
    <Head title="Ping Results" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div>
                <h1 class="text-xl font-bold tracking-tight">Ping Results</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Historical data, trends, and network performance metrics.
                </p>
            </div>

            <PingResultsStatCards :stats="stats" :last-updated="lastUpdated" />

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
