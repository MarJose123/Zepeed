<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { ChevronDown, ChevronRight, HelpCircle } from "@lucide/vue";
import type { ColumnDef } from "@tanstack/vue-table";
import { FlexRender, getCoreRowModel, useVueTable } from "@tanstack/vue-table";
import { h, ref } from "vue";
import PingResultFilter from "@/components/network/PingResultFilter.vue";
import PingResultStatusBadge from "@/components/network/PingResultStatusBadge.vue";
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import type {
    PingResult,
    PingResultFilters,
    PingResultPagination,
    PingResultStatus,
    PingTarget,
} from "@/types/ping";

const props = defineProps<{
    results: PingResult[];
    pagination: PingResultPagination;
    targets: PingTarget[];
    filters: PingResultFilters;
}>();

const expandedRows = ref<Set<string>>(new Set());

const toggleRow = (id: string) => {
    if (expandedRows.value.has(id)) {
        expandedRows.value.delete(id);
    } else {
        expandedRows.value.add(id);
    }
};

const fmtDate = (iso: string) =>
    new Date(iso).toLocaleString("default", {
        month: "short",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
    });

const fmtMs = (v: number | null) =>
    v !== null ? `${Number(v).toFixed(1)} ms` : "—";

// Colour logic for Avg
const avgClass = (v: number | null) => {
    if (v === null) return "text-muted-foreground";

    if (v < 20) return "text-emerald-600 dark:text-emerald-400";

    if (v < 50) return "text-amber-600 dark:text-amber-400";

    return "text-destructive";
};

// Avg column header with tooltip
const AvgHeader = () =>
    h(TooltipProvider, {}, () =>
        h(Tooltip, {}, () => [
            h(TooltipTrigger, { asChild: true }, () =>
                h(
                    "div",
                    {
                        class: "flex cursor-default items-center gap-1 select-none",
                    },
                    [
                        "Avg",
                        h(HelpCircle, {
                            class: "h-3 w-3 text-muted-foreground",
                        }),
                    ],
                ),
            ),
            h(
                TooltipContent,
                { side: "top", class: "max-w-48 space-y-1.5 p-3 text-xs" },
                () => [
                    h(
                        "p",
                        { class: "font-medium mb-1" },
                        "Latency colour coding",
                    ),
                    h("div", { class: "flex items-center gap-2" }, [
                        h("span", {
                            class: "h-2 w-2 shrink-0 rounded-full bg-emerald-500",
                        }),
                        h("span", {}, "< 20 ms — Excellent"),
                    ]),
                    h("div", { class: "flex items-center gap-2" }, [
                        h("span", {
                            class: "h-2 w-2 shrink-0 rounded-full bg-amber-500",
                        }),
                        h("span", {}, "20 – 50 ms — Acceptable"),
                    ]),
                    h("div", { class: "flex items-center gap-2" }, [
                        h("span", {
                            class: "h-2 w-2 shrink-0 rounded-full bg-destructive",
                        }),
                        h("span", {}, "> 50 ms — High latency"),
                    ]),
                ],
            ),
        ]),
    );

const columns: ColumnDef<PingResult>[] = [
    {
        id: "expand",
        header: "",
        cell: ({ row }) =>
            h(
                Button,
                {
                    variant: "ghost",
                    size: "icon",
                    class: "h-6 w-6",
                    onClick: () => toggleRow(row.original.id),
                },
                () =>
                    expandedRows.value.has(row.original.id)
                        ? h(ChevronDown, { class: "h-3.5 w-3.5" })
                        : h(ChevronRight, { class: "h-3.5 w-3.5" }),
            ),
        meta: { headerClass: "w-8" },
    },
    {
        accessorKey: "target_label",
        header: "Target",
        cell: ({ row }) =>
            h("div", [
                h(
                    "div",
                    { class: "text-sm font-medium" },
                    row.original.target_label ?? "—",
                ),
                h(
                    "div",
                    { class: "text-xs text-muted-foreground" },
                    row.original.target_host ?? "",
                ),
            ]),
    },
    {
        accessorKey: "status",
        header: "Status",
        cell: ({ row }) =>
            h(PingResultStatusBadge, {
                status: row.original.status as PingResultStatus,
            }),
    },
    {
        accessorKey: "min_ms",
        header: "Min",
        cell: ({ row }) =>
            h(
                "span",
                { class: "text-sm tabular-nums text-muted-foreground" },
                fmtMs(row.original.min_ms),
            ),
    },
    {
        accessorKey: "avg_ms",
        header: () => h(AvgHeader),
        cell: ({ row }) => {
            const v = row.original.avg_ms;

            return h(
                "span",
                { class: `text-sm font-medium tabular-nums ${avgClass(v)}` },
                fmtMs(v),
            );
        },
    },
    {
        accessorKey: "max_ms",
        header: "Max",
        cell: ({ row }) =>
            h(
                "span",
                { class: "text-sm tabular-nums text-muted-foreground" },
                fmtMs(row.original.max_ms),
            ),
    },
    {
        accessorKey: "packet_loss_percent",
        header: "Pkt Loss",
        cell: ({ row }) => {
            const v = row.original.packet_loss_percent;
            const cls =
                v === 0
                    ? "text-emerald-600 dark:text-emerald-400"
                    : v < 10
                      ? "text-amber-600 dark:text-amber-400"
                      : "text-destructive";

            return h(
                "span",
                { class: `text-sm font-medium tabular-nums ${cls}` },
                `${v}%`,
            );
        },
    },
    {
        accessorKey: "measured_at",
        header: "Measured At",
        cell: ({ row }) =>
            h(
                "span",
                { class: "text-sm text-muted-foreground" },
                fmtDate(row.original.measured_at),
            ),
    },
];

const table = useVueTable({
    get data() {
        return props.results;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
});

const prevUrl = () => {
    if (props.pagination.current_page <= 1) return null;

    const p = new URLSearchParams(window.location.search);
    p.set("page", String(props.pagination.current_page - 1));

    return `${window.location.pathname}?${p.toString()}`;
};

const nextUrl = () => {
    if (props.pagination.current_page >= props.pagination.last_page)
        return null;

    const p = new URLSearchParams(window.location.search);
    p.set("page", String(props.pagination.current_page + 1));

    return `${window.location.pathname}?${p.toString()}`;
};
</script>

<template>
    <div>
        <div class="flex items-center justify-end py-4">
            <PingResultFilter :targets="targets" :filters="filters" />
        </div>

        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow
                        v-for="hg in table.getHeaderGroups()"
                        :key="hg.id"
                    >
                        <TableHead
                            v-for="header in hg.headers"
                            :key="header.id"
                            :class="
                                (header.column.columnDef.meta as any)
                                    ?.headerClass
                            "
                        >
                            <FlexRender
                                v-if="!header.isPlaceholder"
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <template v-if="table.getRowModel().rows.length">
                        <template
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                        >
                            <TableRow class="hover:bg-muted/50">
                                <TableCell
                                    v-for="cell in row.getVisibleCells()"
                                    :key="cell.id"
                                    :class="
                                        (cell.column.columnDef.meta as any)
                                            ?.cellClass
                                    "
                                >
                                    <FlexRender
                                        :render="cell.column.columnDef.cell"
                                        :props="cell.getContext()"
                                    />
                                </TableCell>
                            </TableRow>

                            <!-- Expanded raw output -->
                            <TableRow
                                v-if="expandedRows.has(row.original.id)"
                                class="bg-muted/30 hover:bg-muted/30"
                            >
                                <TableCell
                                    :colspan="columns.length"
                                    class="px-4 pb-3 pt-0"
                                >
                                    <div
                                        class="grid grid-cols-2 gap-4 pt-2 text-xs sm:grid-cols-4"
                                    >
                                        <div>
                                            <p class="text-muted-foreground">
                                                Packets
                                            </p>
                                            <p class="font-medium">
                                                {{ row.original.packets_sent }}
                                                sent /
                                                {{
                                                    row.original
                                                        .packets_received
                                                }}
                                                received
                                            </p>
                                        </div>
                                        <div
                                            v-if="
                                                row.original.stddev_ms !== null
                                            "
                                        >
                                            <p class="text-muted-foreground">
                                                Std Dev
                                            </p>
                                            <p class="font-medium">
                                                {{ row.original.stddev_ms }} ms
                                            </p>
                                        </div>
                                        <div v-if="row.original.failure_reason">
                                            <p class="text-muted-foreground">
                                                Failure Reason
                                            </p>
                                            <Badge
                                                variant="outline"
                                                class="text-xs text-destructive border-destructive/30"
                                            >
                                                {{
                                                    row.original.failure_reason
                                                }}
                                            </Badge>
                                        </div>
                                    </div>
                                    <div
                                        v-if="row.original.raw_output"
                                        class="mt-2"
                                    >
                                        <p
                                            class="mb-1 text-xs text-muted-foreground"
                                        >
                                            Raw Output
                                        </p>
                                        <pre
                                            class="max-h-48 overflow-x-auto rounded-md border border-border bg-background p-2 text-[11px] leading-relaxed"
                                            >{{ row.original.raw_output }}</pre
                                        >
                                    </div>
                                </TableCell>
                            </TableRow>
                        </template>
                    </template>

                    <TableRow v-else>
                        <TableCell
                            :colspan="columns.length"
                            class="h-24 text-center text-sm text-muted-foreground"
                        >
                            No results found for the selected filters.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between py-4">
            <p class="text-sm text-muted-foreground">
                Showing
                <span class="font-medium text-foreground">{{
                    pagination.from ?? 0
                }}</span>
                –
                <span class="font-medium text-foreground">{{
                    pagination.to ?? 0
                }}</span>
                of
                <span class="font-medium text-foreground">{{
                    pagination.total
                }}</span>
                results
            </p>
            <div class="flex gap-2">
                <Button variant="outline" size="sm" :disabled="!prevUrl()">
                    <Link v-if="prevUrl()" :href="prevUrl()!" preserve-scroll>
                        Previous
                    </Link>
                    <span v-else>Previous</span>
                </Button>
                <Button variant="outline" size="sm" :disabled="!nextUrl()">
                    <Link v-if="nextUrl()" :href="nextUrl()!" preserve-scroll>
                        Next
                    </Link>
                    <span v-else>Next</span>
                </Button>
            </div>
        </div>
    </div>
</template>
