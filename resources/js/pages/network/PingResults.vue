<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { FileDown } from "@lucide/vue";
import { ref } from "vue";
import PingExportDialog from "@/components/network/PingExportDialog.vue";
import PingResultsChart from "@/components/network/PingResultsChart.vue";
import PingResultsStatCards from "@/components/network/PingResultsStatCards.vue";
import PingResultsTable from "@/components/network/PingResultsTable.vue";
import { Button } from "@/components/ui/button";
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
const exportOpen = ref(false);

usePingResultsChannel({
    onCompleted(payload) {
        lastUpdated.value = payload.measured_at;
        router.reload({ only: ["results", "stats", "trend"] });
    },
});
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
                <Button
                    variant="outline"
                    size="sm"
                    class="gap-1.5"
                    @click="exportOpen = true"
                >
                    <FileDown class="size-3.5" />
                    Export
                </Button>
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

    <PingExportDialog v-model:open="exportOpen" :targets="targets" />
</template>
