<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import type { TPagedResource } from "@/types";
import type { TSpeedResult } from "@/types/speed-result";

const props = defineProps<{
    results: TPagedResource<TSpeedResult>;
    metric: "download" | "upload" | "ping";
    accentVar: string;
}>();

const unit = props.metric === "ping" ? "ms" : "Mbps";

const metricLabel: Record<string, string> = {
    download: "↓ Download",
    upload: "↑ Upload",
    ping: "◎ Ping",
};

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString("default", {
        year: "numeric",
        month: "short",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
    });
}

function metricValue(row: TSpeedResult): number {
    const map: Record<string, keyof TSpeedResult> = {
        download: "download",
        upload: "upload",
        ping: "ping",
    };

    return (row[map[props.metric]] as number) ?? 0;
}

function barWidth(row: TSpeedResult, allRows: TSpeedResult[]): number {
    const max = Math.max(...allRows.map((r) => metricValue(r)));

    if (max === 0) return 0;

    const v = metricValue(row);

    return props.metric === "ping"
        ? Math.min(100, Math.round((v / 200) * 100))
        : Math.round((v / max) * 100);
}

function metricClass(row: TSpeedResult): "good" | "warn" | "bad" {
    const v = metricValue(row);

    if (props.metric === "ping") {
        if (v <= 20) return "good";

        if (v <= 60) return "warn";

        return "bad";
    }

    const threshold = props.metric === "download" ? 25 : 10;

    if (v >= 100) return "good";

    if (v >= threshold) return "warn";

    return "bad";
}
</script>

<template>
    <div class="rounded-xl border border-border bg-card overflow-hidden">
        <!-- Table header row -->
        <div
            class="flex items-center justify-between px-4 py-3 border-b border-border"
        >
            <span
                class="font-mono text-[10px] uppercase tracking-[0.08em] text-muted-foreground"
            >
                Test log
            </span>
            <span
                class="font-mono text-[10px] px-2 py-0.5 border border-border rounded-full text-muted-foreground"
            >
                {{ results.meta.total }} records
            </span>
        </div>

        <Table>
            <TableHeader>
                <TableRow class="hover:bg-transparent border-border">
                    <TableHead
                        class="font-mono text-[10px] uppercase tracking-[0.07em]"
                        >Timestamp</TableHead
                    >
                    <TableHead
                        class="font-mono text-[10px] uppercase tracking-[0.07em]"
                        >Provider</TableHead
                    >
                    <TableHead
                        v-if="metric === 'ping'"
                        class="font-mono text-[10px] uppercase tracking-[0.07em] text-right"
                    >
                        Jitter
                    </TableHead>
                    <TableHead
                        class="font-mono text-[10px] uppercase tracking-[0.07em] text-right"
                        :style="`color:${accentVar}`"
                    >
                        {{ metricLabel[metric] }}
                    </TableHead>
                    <TableHead
                        class="font-mono text-[10px] uppercase tracking-[0.07em] text-right"
                        >Share</TableHead
                    >
                </TableRow>
            </TableHeader>

            <TableBody>
                <template v-if="results.data.length > 0">
                    <TableRow
                        v-for="row in results.data"
                        :key="row.id"
                        class="hover:bg-transparent border-border"
                    >
                        <!-- Timestamp -->
                        <TableCell
                            class="font-mono text-[11.5px] text-muted-foreground"
                        >
                            {{ formatDate(row.measured_at) }}
                        </TableCell>

                        <!-- Provider -->
                        <TableCell>
                            <Badge
                                variant="outline"
                                class="font-mono text-[10px]"
                            >
                                {{ row.provider_name }}
                            </Badge>
                        </TableCell>

                        <!-- Jitter (ping only) -->
                        <TableCell v-if="metric === 'ping'" class="text-right">
                            <span
                                class="font-mono text-xs text-muted-foreground"
                            >
                                {{ row.jitter ?? "—"
                                }}<span class="opacity-45 ml-0.5 text-[9.5px]"
                                    >ms</span
                                >
                            </span>
                        </TableCell>

                        <!-- Metric value + mini bar -->
                        <TableCell class="text-right">
                            <div class="flex flex-col items-end gap-0.5">
                                <span
                                    class="font-mono text-sm font-semibold leading-none"
                                    :class="{
                                        'text-emerald-600 dark:text-emerald-400':
                                            metricClass(row) === 'good',
                                        'text-amber-600 dark:text-amber-400':
                                            metricClass(row) === 'warn',
                                        'text-destructive':
                                            metricClass(row) === 'bad',
                                    }"
                                >
                                    {{ metricValue(row)
                                    }}<span
                                        class="text-[9.5px] font-normal opacity-45 ml-0.5"
                                        >{{ unit }}</span
                                    >
                                </span>
                                <div
                                    class="w-[52px] h-[2px] rounded-full bg-border overflow-hidden"
                                >
                                    <div
                                        class="h-full rounded-full"
                                        :class="{
                                            'bg-emerald-500':
                                                metricClass(row) === 'good',
                                            'bg-amber-500':
                                                metricClass(row) === 'warn',
                                            'bg-destructive':
                                                metricClass(row) === 'bad',
                                        }"
                                        :style="`width:${barWidth(row, results.data)}%`"
                                    />
                                </div>
                            </div>
                        </TableCell>

                        <!-- Share -->
                        <TableCell class="text-right">
                            <a
                                v-if="row.share_url"
                                :href="row.share_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-mono text-[10px] text-muted-foreground hover:text-foreground transition-colors"
                            >
                                ↗ view
                            </a>
                            <span
                                v-else
                                class="font-mono text-[10px] text-muted-foreground"
                                >—</span
                            >
                        </TableCell>
                    </TableRow>
                </template>

                <TableRow v-else>
                    <TableCell
                        :colspan="metric === 'ping' ? 5 : 4"
                        class="h-32 text-center font-mono text-xs text-muted-foreground"
                    >
                        No results found for the selected filters.
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <!-- Pagination -->
        <div
            class="flex items-center justify-between px-4 py-2.5 border-t border-border"
        >
            <span class="font-mono text-[10px] text-muted-foreground">
                Showing {{ results.meta.from ?? 0 }}–{{
                    results.meta.to ?? 0
                }}
                of {{ results.meta.total }} results
            </span>
            <div class="flex gap-1">
                <Button
                    v-if="results.links.prev"
                    variant="outline"
                    size="sm"
                    class="font-mono text-[10px] h-7"
                    as-child
                >
                    <Link :href="results.links.prev" preserve-scroll
                        >← Prev</Link
                    >
                </Button>
                <Button
                    v-if="results.links.next"
                    variant="outline"
                    size="sm"
                    class="font-mono text-[10px] h-7"
                    as-child
                >
                    <Link :href="results.links.next" preserve-scroll
                        >Next →</Link
                    >
                </Button>
            </div>
        </div>
    </div>
</template>
