<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import PublicAlertHistory from "@/components/public/PublicAlertHistory.vue";
import PublicResultsTable from "@/components/public/PublicResultsTable.vue";
import PublicStatCard from "@/components/public/PublicStatCard.vue";
import PublicTrendChart from "@/components/public/PublicTrendChart.vue";
import PublicLayout from "@/layouts/PublicLayout.vue";
import type {
    PublicStats,
    TrendPoint,
    PublicSpeedResult,
    PublicAlertItem,
} from "@/types/public";

defineProps<{
    stats: PublicStats;
    trend: TrendPoint[];
    recentResults: PublicSpeedResult[];
    alertHistory: PublicAlertItem[];
}>();
</script>

<template>
    <Head title="Public Dashboard" />
    <PublicLayout>
        <div class="flex flex-col gap-4">
            <!-- Overview -->
            <section>
                <p
                    class="text-muted-foreground mb-2 text-[11px] font-medium uppercase tracking-wider"
                >
                    Overview · last 24 h
                </p>
                <div class="grid grid-cols-5 gap-2">
                    <PublicStatCard
                        label="Total tests"
                        :value="stats.total_tests.toLocaleString()"
                    />
                    <PublicStatCard
                        label="Avg download"
                        :value="stats.avg_download"
                        unit="Mbps"
                    />
                    <PublicStatCard
                        label="Avg upload"
                        :value="stats.avg_upload"
                        unit="Mbps"
                    />
                    <PublicStatCard
                        label="Avg latency"
                        :value="stats.avg_ping"
                        unit="ms"
                    />
                    <PublicStatCard
                        label="Providers"
                        :value="stats.provider_count"
                    />
                </div>
            </section>

            <!-- 24-hour trend -->
            <section>
                <p
                    class="text-muted-foreground mb-2 text-[11px] font-medium uppercase tracking-wider"
                >
                    24-hour trend · all providers
                </p>
                <div class="grid grid-cols-3 gap-2">
                    <PublicTrendChart
                        :points="trend"
                        metric="download"
                        label="Download"
                        unit="Mbps"
                    />
                    <PublicTrendChart
                        :points="trend"
                        metric="upload"
                        label="Upload"
                        unit="Mbps"
                    />
                    <PublicTrendChart
                        :points="trend"
                        metric="ping"
                        label="Latency"
                        unit="ms"
                    />
                </div>
            </section>

            <!-- Recent results -->
            <section>
                <p
                    class="text-muted-foreground mb-2 text-[11px] font-medium uppercase tracking-wider"
                >
                    Recent results
                </p>
                <PublicResultsTable :results="recentResults" />
            </section>

            <!-- Alert history -->
            <section>
                <p
                    class="text-muted-foreground mb-2 text-[11px] font-medium uppercase tracking-wider"
                >
                    Alert history
                </p>
                <PublicAlertHistory :alerts="alertHistory" />
            </section>
        </div>
    </PublicLayout>
</template>
