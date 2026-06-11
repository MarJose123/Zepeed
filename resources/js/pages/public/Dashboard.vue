<script setup lang="ts">
import { router, usePage } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";
import PublicAlertHistory from "@/components/public/PublicAlertHistory.vue";
import PublicResultsTable from "@/components/public/PublicResultsTable.vue";
import PublicStatCard from "@/components/public/PublicStatCard.vue";
import PublicTrendChart from "@/components/public/PublicTrendChart.vue";
import { usePublicDashboardRefresh } from "@/composables/usePublicDashboardRefresh";
import PublicLayout from "@/layouts/PublicLayout.vue";
import type {
    PublicAlertItem,
    PublicDashboardRefreshPayload,
    PublicSpeedResult,
    PublicStats,
    TrendPoint,
} from "@/types/public";

const props = defineProps<{
    stats: PublicStats;
    trend: TrendPoint[];
    recentResults: PublicSpeedResult[];
    alertHistory: PublicAlertItem[];
}>();

const page = usePage<{
    stats: PublicStats;
    trend: TrendPoint[];
    recentResults: PublicSpeedResult[];
}>();

const stats = ref<PublicStats>(props.stats);
const trend = ref<TrendPoint[]>(props.trend);
const recentResults = ref<PublicSpeedResult[]>(props.recentResults);

usePublicDashboardRefresh((payload: PublicDashboardRefreshPayload) => {
    const incoming: PublicSpeedResult = {
        id: `pending-${payload.result.measured_at}`,
        provider_name: payload.result.provider_name,
        download_mbps: payload.result.download_mbps,
        upload_mbps: payload.result.upload_mbps,
        ping_ms: payload.result.ping_ms,
        jitter_ms: payload.result.jitter_ms,
        measured_at: payload.result.measured_at,
    };

    recentResults.value = [incoming, ...recentResults.value].slice(0, 10);

    router.reload({
        only: ["stats", "trend", "recentResults"],
        onSuccess: () => {
            stats.value = page.props.stats;
            trend.value = page.props.trend;
            recentResults.value = page.props.recentResults;
        },
    });
});
</script>

<template>
    <Head title="Public Dashboard" />
    <PublicLayout>
        <div class="flex flex-col gap-4">
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
                        icon="database"
                    />
                    <PublicStatCard
                        label="Avg download"
                        :value="stats.avg_download"
                        icon="arrow-down"
                        unit="Mbps"
                    />
                    <PublicStatCard
                        label="Avg upload"
                        :value="stats.avg_upload"
                        icon="arrow-up"
                        unit="Mbps"
                    />
                    <PublicStatCard
                        label="Avg latency"
                        :value="stats.avg_ping"
                        icon="activity"
                        unit="ms"
                    />
                    <PublicStatCard
                        label="Providers"
                        :value="stats.provider_count"
                        icon="server"
                    />
                </div>
            </section>

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

            <section>
                <p
                    class="text-muted-foreground mb-2 text-[11px] font-medium uppercase tracking-wider"
                >
                    Recent results
                </p>
                <PublicResultsTable :results="recentResults" />
            </section>

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
