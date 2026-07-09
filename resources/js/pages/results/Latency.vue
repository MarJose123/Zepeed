<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { FileDown } from "@lucide/vue";
import { ref } from "vue";
import { downloadColumns } from "@/components/speed-result/columns/download";
import DataTable from "@/components/speed-result/DataTable.vue";
import DateRangeFilter from "@/components/speed-result/DateRangeFilter.vue";
import ExportDialog from "@/components/speed-result/ExportDialog.vue";
import ResultsFilter from "@/components/speed-result/ResultsFilter.vue";
import ResultsStatCards from "@/components/speed-result/ResultsStatCards.vue";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TPagedResource } from "@/types";
import type {
    TSpeedResult,
    TSpeedResultFilters,
    TSpeedResultStats,
    TProviderOption,
} from "@/types/speed-result";

defineProps<{
    results: TPagedResource<TSpeedResult>;
    providers: TProviderOption[];
    months: string[];
    filters: TSpeedResultFilters;
    stats: TSpeedResultStats;
}>();

const ACCENT = "oklch(0.48 0.22 305)";
const ROUTE_NAME = "speedtest.results.latency";
const exportOpen = ref(false);
</script>

<template>
    <Head title="Latency Results" />
    <AppLayout
        :breadcrumbs="[
            { title: 'Speedtest Result', href: '#' },
            { title: 'Latency Results', href: route(ROUTE_NAME) },
        ]"
    >
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-bold tracking-tight">
                        Download Results
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Historical download speed measurements across all
                        providers.
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

            <ResultsStatCards
                :stats="stats"
                metric="download"
                :accent-var="ACCENT"
            />

            <DataTable
                :columns="downloadColumns"
                :results="results"
                :filters="filters"
                :route-name="ROUTE_NAME"
            >
                <template #toolbar>
                    <ResultsFilter
                        :providers="providers"
                        :filters="filters"
                        :route-name="ROUTE_NAME"
                    />
                    <DateRangeFilter
                        :months="months"
                        :filters="filters"
                        :route-name="ROUTE_NAME"
                    />
                </template>
            </DataTable>
        </div>
    </AppLayout>

    <ExportDialog
        v-model:open="exportOpen"
        :filters="filters"
        :months="months"
        :providers="providers"
        :route-name="ROUTE_NAME"
        module-label="Download Results"
    />
</template>
