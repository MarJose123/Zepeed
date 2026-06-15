<script setup lang="ts">
import { Activity, Radio, TrendingDown } from "@lucide/vue";
import { Card, CardContent } from "@/components/ui/card";
import type { PingResultStats } from "@/types/ping";

defineProps<{
    stats: PingResultStats;
    lastUpdated: string | null;
}>();

const fmt = (v: number | null, digits = 1) =>
    v !== null ? Number(v).toFixed(digits) : "—";

const fmtTime = (iso: string | null): string => {
    if (!iso) return "";

    return new Date(iso).toLocaleTimeString("default", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
    });
};
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <!-- Tests Run -->
        <Card class="border-l-[3px] border-l-primary">
            <CardContent class="p-4">
                <div class="flex items-start justify-between">
                    <p class="text-xs font-medium text-muted-foreground">
                        Tests Run
                    </p>
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10"
                    >
                        <Activity class="h-4 w-4 text-primary" />
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold tracking-tight">
                    {{ stats.total_tests }}
                </p>
                <p class="mt-1 text-[11px] text-muted-foreground">
                    in selected window
                </p>
            </CardContent>
        </Card>

        <!-- Avg Latency -->
        <Card
            class="border-l-[3px]"
            :class="
                stats.avg_latency_ms === null
                    ? 'border-l-muted-foreground'
                    : stats.avg_latency_ms < 20
                      ? 'border-l-emerald-500'
                      : stats.avg_latency_ms < 50
                        ? 'border-l-amber-500'
                        : 'border-l-destructive'
            "
        >
            <CardContent class="p-4">
                <div class="flex items-start justify-between">
                    <p class="text-xs font-medium text-muted-foreground">
                        Avg Latency
                    </p>
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg"
                        :class="
                            stats.avg_latency_ms === null
                                ? 'bg-muted'
                                : stats.avg_latency_ms < 20
                                  ? 'bg-emerald-500/10'
                                  : stats.avg_latency_ms < 50
                                    ? 'bg-amber-500/10'
                                    : 'bg-destructive/10'
                        "
                    >
                        <Radio
                            class="h-4 w-4"
                            :class="
                                stats.avg_latency_ms === null
                                    ? 'text-muted-foreground'
                                    : stats.avg_latency_ms < 20
                                      ? 'text-emerald-500'
                                      : stats.avg_latency_ms < 50
                                        ? 'text-amber-500'
                                        : 'text-destructive'
                            "
                        />
                    </span>
                </div>
                <p
                    class="mt-2 text-2xl font-bold tracking-tight"
                    :class="
                        stats.avg_latency_ms === null
                            ? 'text-foreground'
                            : stats.avg_latency_ms < 20
                              ? 'text-emerald-600 dark:text-emerald-400'
                              : stats.avg_latency_ms < 50
                                ? 'text-amber-600 dark:text-amber-400'
                                : 'text-destructive'
                    "
                >
                    {{ fmt(stats.avg_latency_ms)
                    }}<span
                        class="text-xs font-normal text-muted-foreground ml-1"
                        >ms</span
                    >
                </p>
                <p class="mt-1 text-[11px] text-muted-foreground">
                    average round-trip time
                </p>
            </CardContent>
        </Card>

        <!-- Avg Packet Loss -->
        <Card
            class="border-l-[3px]"
            :class="
                stats.avg_packet_loss === null
                    ? 'border-l-muted-foreground'
                    : stats.avg_packet_loss === 0
                      ? 'border-l-emerald-500'
                      : stats.avg_packet_loss < 5
                        ? 'border-l-amber-500'
                        : 'border-l-destructive'
            "
        >
            <CardContent class="p-4">
                <div class="flex items-start justify-between">
                    <p class="text-xs font-medium text-muted-foreground">
                        Avg Packet Loss
                    </p>
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg"
                        :class="
                            stats.avg_packet_loss === null
                                ? 'bg-muted'
                                : stats.avg_packet_loss === 0
                                  ? 'bg-emerald-500/10'
                                  : stats.avg_packet_loss < 5
                                    ? 'bg-amber-500/10'
                                    : 'bg-destructive/10'
                        "
                    >
                        <TrendingDown
                            class="h-4 w-4"
                            :class="
                                stats.avg_packet_loss === null
                                    ? 'text-muted-foreground'
                                    : stats.avg_packet_loss === 0
                                      ? 'text-emerald-500'
                                      : stats.avg_packet_loss < 5
                                        ? 'text-amber-500'
                                        : 'text-destructive'
                            "
                        />
                    </span>
                </div>
                <p
                    class="mt-2 text-2xl font-bold tracking-tight"
                    :class="
                        stats.avg_packet_loss === null
                            ? 'text-foreground'
                            : stats.avg_packet_loss === 0
                              ? 'text-emerald-600 dark:text-emerald-400'
                              : stats.avg_packet_loss < 5
                                ? 'text-amber-600 dark:text-amber-400'
                                : 'text-destructive'
                    "
                >
                    {{ fmt(stats.avg_packet_loss)
                    }}<span
                        class="text-xs font-normal text-muted-foreground ml-1"
                        >%</span
                    >
                </p>
                <p class="mt-1 text-[11px] text-muted-foreground">
                    across all targets
                </p>
            </CardContent>
        </Card>
    </div>

    <!-- Last updated indicator -->
    <p
        v-if="lastUpdated"
        class="flex items-center gap-1.5 text-[11px] text-muted-foreground"
    >
        <span class="relative flex h-2 w-2">
            <span
                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
            />
            <span
                class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
            />
        </span>
        Live · last result at {{ fmtTime(lastUpdated) }}
    </p>
</template>
