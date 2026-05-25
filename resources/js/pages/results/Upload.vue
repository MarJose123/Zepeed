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
            {
                title: 'Upload Results',
                href: route('speedtest.results.upload'),
            },
        ]"
    >
        <div class="flex flex-col gap-5 p-6">
            <div>
                <p
                    class="font-mono text-[10px] uppercase tracking-[0.1em] text-muted-foreground mb-1"
                >
                    results / upload
                </p>
                <h1
                    class="text-xl font-semibold tracking-tight flex items-center gap-2.5"
                >
                    Upload Speed
                    <span
                        class="font-mono text-[10.5px] font-medium px-2.5 py-0.5 rounded-full border border-border bg-[oklch(0.97_0.025_260)] text-[oklch(0.48_0.19_260)]"
                        >↑ Mbps</span
                    >
                </h1>
                <p class="font-mono text-[11px] text-muted-foreground mt-0.5">
                    {{ stats.total }} records
                </p>
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
