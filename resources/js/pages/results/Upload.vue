<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { uploadColumns } from "@/components/speed-result/columns/upload";
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

const ACCENT = "oklch(0.48 0.19 260)";
</script>

<template>
    <Head title="Upload Results" />

    <AppLayout
        :breadcrumbs="[
            { title: 'Speedtest Result', href: '#' },
            {
                title: 'Upload Results',
                href: route('speedtest.results.upload'),
            },
        ]"
    >
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-bold tracking-tight">
                        Upload Results
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Historical upload speed measurements across all
                        providers.
                    </p>
                </div>
            </div>

            <ResultsStatCards
                :stats="stats"
                metric="upload"
                :accent-var="ACCENT"
            />

            <DataTable :columns="uploadColumns" :results="results">
                <template #toolbar>
                    <ResultsFilter
                        :providers="providers"
                        :months="months"
                        :filters="filters"
                        route-name="speedtest.results.upload"
                    />
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
