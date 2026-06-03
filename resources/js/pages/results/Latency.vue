<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { latencyColumns } from "@/components/speed-result/columns/latency";
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

const ACCENT = "oklch(0.48 0.22 305)";
</script>

<template>
    <Head title="Ping Latency" />

    <AppLayout
        :breadcrumbs="[
            {
                title: 'Speedtest Result',
                href: '#',
            },
            { title: 'Ping Latency', href: route('speedtest.results.latency') },
        ]"
    >
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-bold tracking-tight">
                        Ping Latency
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Historical ping and jitter measurements across all
                        providers.
                    </p>
                </div>
            </div>

            <ResultsStatCards
                :stats="stats"
                metric="ping"
                :accent-var="ACCENT"
            />

            <DataTable :columns="latencyColumns" :results="results">
                <template #toolbar>
                    <ResultsFilter
                        :providers="providers"
                        :months="months"
                        :filters="filters"
                        route-name="speedtest.results.ping"
                    />
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
