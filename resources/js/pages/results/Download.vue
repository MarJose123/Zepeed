<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { downloadColumns } from "@/components/speed-result/columns/download";
import DataTable from "@/components/speed-result/DataTable.vue";
import ResultsFilter from "@/components/speed-result/ResultsFilter.vue";
import ResultsStatCards from "@/components/speed-result/ResultsStatCards.vue";
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

const ACCENT = "oklch(0.52 0.17 155)";
</script>

<template>
    <Head title="Download Results" />

    <AppLayout
        :breadcrumbs="[
            { title: 'Speedtest Result', href: '#' },
            {
                title: 'Download Results',
                href: route('speedtest.results.download'),
            },
        ]"
    >
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-bold tracking-tight">
                        Download Results
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Historical download speed measurements across all
                        providers.
                    </p>
                </div>
            </div>

            <ResultsStatCards
                :stats="stats"
                metric="download"
                :accent-var="ACCENT"
            />

            <DataTable :columns="downloadColumns" :results="results">
                <template #toolbar>
                    <ResultsFilter
                        :providers="providers"
                        :months="months"
                        :filters="filters"
                        route-name="speedtest.results.download"
                    />
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
