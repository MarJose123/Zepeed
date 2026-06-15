<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import type { PublicPingResult } from "@/types/public";

defineProps<{ results: PublicPingResult[] }>();

const statusConfig = (status: PublicPingResult["status"]) =>
    ({
        success: {
            label: "Success",
            class: "text-emerald-600 dark:text-emerald-400",
        },
        partial: {
            label: "Partial",
            class: "text-amber-600 dark:text-amber-400",
        },
        failed: { label: "Failed", class: "text-destructive" },
    })[status];

const avgClass = (v: number | null) => {
    if (v === null) return "text-muted-foreground";

    if (v < 20) return "text-emerald-600 dark:text-emerald-400";

    if (v < 50) return "text-amber-600 dark:text-amber-400";

    return "text-destructive";
};

const lossClass = (v: number) => {
    if (v === 0) return "text-emerald-600 dark:text-emerald-400";

    if (v < 10) return "text-amber-600 dark:text-amber-400";

    return "text-destructive";
};

const fmtMs = (v: number | null) =>
    v !== null ? `${Number(v).toFixed(1)} ms` : "—";

const fmtDate = (iso: string) =>
    new Date(iso).toLocaleString(undefined, {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-border">
        <Table>
            <TableHeader>
                <TableRow class="bg-muted/50 hover:bg-muted/50">
                    <TableHead class="text-[11px]">Target</TableHead>
                    <TableHead class="text-[11px]">Status</TableHead>
                    <TableHead class="text-[11px]">Avg Latency</TableHead>
                    <TableHead class="text-[11px]">Pkt Loss</TableHead>
                    <TableHead class="text-[11px]">Measured At</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow
                    v-for="row in results"
                    :key="row.id"
                    class="hover:bg-muted/50"
                >
                    <TableCell>
                        <p class="text-sm font-medium">
                            {{ row.target_label }}
                        </p>
                        <p class="text-[11px] text-muted-foreground">
                            {{ row.target_host }}
                        </p>
                    </TableCell>
                    <TableCell>
                        <span
                            class="text-xs font-medium"
                            :class="statusConfig(row.status).class"
                        >
                            {{ statusConfig(row.status).label }}
                        </span>
                    </TableCell>
                    <TableCell>
                        <span
                            class="text-sm tabular-nums"
                            :class="avgClass(row.avg_ms)"
                        >
                            {{ fmtMs(row.avg_ms) }}
                        </span>
                    </TableCell>
                    <TableCell>
                        <span
                            class="text-sm tabular-nums"
                            :class="lossClass(row.packet_loss_percent)"
                        >
                            {{ row.packet_loss_percent }}%
                        </span>
                    </TableCell>
                    <TableCell class="text-sm text-muted-foreground">
                        {{ fmtDate(row.measured_at) }}
                    </TableCell>
                </TableRow>

                <TableRow v-if="results.length === 0">
                    <TableCell
                        colspan="5"
                        class="py-6 text-center text-sm text-muted-foreground"
                    >
                        No ping results yet.
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
        <div class="border-border border-t px-3 py-2 text-center">
            <Link
                :href="route('login')"
                class="text-muted-foreground inline-flex items-center gap-1.5 text-[11px] hover:underline"
            >
                <svg
                    class="h-3 w-3"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                    />
                </svg>
                Sign in to view full history
            </Link>
        </div>
    </div>
</template>
