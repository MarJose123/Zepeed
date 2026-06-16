<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { MoreHorizontal, Pencil, Play, Trash2 } from "@lucide/vue";
import type { ColumnDef } from "@tanstack/vue-table";
import { FlexRender, getCoreRowModel, useVueTable } from "@tanstack/vue-table";
import { h } from "vue";
import PingStatusBadge from "@/components/network/PingStatusBadge.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Switch } from "@/components/ui/switch";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import type { PingTarget } from "@/types/ping";

const props = defineProps<{ targets: PingTarget[] }>();

const emit = defineEmits<{
    edit: [target: PingTarget];
    delete: [target: PingTarget];
    runNow: [target: PingTarget];
}>();

const toggleEnabled = (target: PingTarget) => {
    router.patch(
        route(
            "speedtest.network.ping-targets.update",
            { pingTarget: target.id },
            false,
        ),
        { is_enabled: !target.is_enabled },
        { preserveScroll: true },
    );
};

const formatRelative = (iso: string | null): string => {
    if (!iso) return "—";

    const diff = Date.now() - new Date(iso).getTime();
    const mins = Math.floor(diff / 60_000);

    if (mins < 1) return "just now";

    if (mins < 60) return `${mins}m ago`;

    const hrs = Math.floor(mins / 60);

    if (hrs < 24) return `${hrs}h ago`;

    return `${Math.floor(hrs / 24)}d ago`;
};

const columns: ColumnDef<PingTarget>[] = [
    {
        accessorKey: "label",
        header: "Target",
        cell: ({ row }) =>
            h("div", [
                h("div", { class: "text-sm font-medium" }, row.original.label),
                h(
                    "div",
                    { class: "text-xs text-muted-foreground" },
                    row.original.host,
                ),
            ]),
    },
    {
        accessorKey: "status",
        header: "Status",
        cell: ({ row }) => h(PingStatusBadge, { status: row.original.status }),
    },
    {
        accessorKey: "last_avg_ms",
        header: "Avg Latency",
        cell: ({ row }) => {
            const v = row.original.last_avg_ms;

            if (v === null)
                return h(
                    "span",
                    { class: "text-sm text-muted-foreground" },
                    "—",
                );

            const cls =
                v < 20
                    ? "text-emerald-600 dark:text-emerald-400"
                    : v < 50
                      ? "text-amber-600 dark:text-amber-400"
                      : "text-destructive";

            return h(
                "span",
                { class: `text-sm font-medium tabular-nums ${cls}` },
                [
                    `${v}`,
                    h(
                        "span",
                        {
                            class: "text-xs font-normal text-muted-foreground ml-0.5",
                        },
                        " ms",
                    ),
                ],
            );
        },
    },
    {
        accessorKey: "last_loss_percent",
        header: "Pkt Loss",
        cell: ({ row }) => {
            const v = row.original.last_loss_percent;

            if (v === null)
                return h(
                    "span",
                    { class: "text-sm text-muted-foreground" },
                    "—",
                );

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
        accessorKey: "packets",
        header: "Packets",
        cell: ({ row }) =>
            h(
                Badge,
                { variant: "outline", class: "text-xs" },
                () => `${row.original.packets} pkts`,
            ),
    },
    {
        accessorKey: "is_enabled",
        header: "Enabled",
        cell: ({ row }) =>
            h(Switch, {
                modelValue: row.original.is_enabled,
                class: "data-[state=checked]:bg-primary",
                onClick: (e: Event) => {
                    e.stopPropagation();
                    toggleEnabled(row.original);
                },
            }),
    },
    {
        accessorKey: "last_tested_at",
        header: "Last Test",
        cell: ({ row }) =>
            h(
                "span",
                { class: "text-sm text-muted-foreground" },
                formatRelative(row.original.last_tested_at),
            ),
    },
    {
        id: "actions",
        header: "",
        cell: ({ row }) =>
            h("div", { class: "flex items-center justify-end gap-1" }, [
                h(
                    Button,
                    {
                        variant: "ghost",
                        size: "icon",
                        class: "h-7 w-7",
                        title: "Run ping now",
                        onClick: (e: Event) => {
                            e.stopPropagation();
                            emit("runNow", row.original);
                        },
                    },
                    () => h(Play, { class: "h-3.5 w-3.5" }),
                ),
                h(
                    DropdownMenu,
                    {},
                    {
                        default: () => [
                            h(DropdownMenuTrigger, { asChild: true }, () =>
                                h(
                                    Button,
                                    {
                                        variant: "ghost",
                                        size: "icon",
                                        class: "h-7 w-7",
                                        onClick: (e: Event) =>
                                            e.stopPropagation(),
                                    },
                                    () =>
                                        h(MoreHorizontal, {
                                            class: "h-3.5 w-3.5",
                                        }),
                                ),
                            ),
                            h(DropdownMenuContent, { align: "end" }, () => [
                                h(
                                    DropdownMenuItem,
                                    {
                                        onClick: () =>
                                            emit("edit", row.original),
                                    },
                                    () => [
                                        h(Pencil, {
                                            class: "mr-2 h-3.5 w-3.5",
                                        }),
                                        "Edit",
                                    ],
                                ),
                                h(DropdownMenuSeparator),
                                h(
                                    DropdownMenuItem,
                                    {
                                        class: "text-destructive focus:text-destructive",
                                        onClick: () =>
                                            emit("delete", row.original),
                                    },
                                    () => [
                                        h(Trash2, {
                                            class: "mr-2 h-3.5 w-3.5",
                                        }),
                                        "Delete",
                                    ],
                                ),
                            ]),
                        ],
                    },
                ),
            ]),
    },
];

const table = useVueTable({
    get data() {
        return props.targets;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
});
</script>

<template>
    <div class="rounded-md border">
        <Table>
            <TableHeader>
                <TableRow v-for="hg in table.getHeaderGroups()" :key="hg.id">
                    <TableHead v-for="header in hg.headers" :key="header.id">
                        <FlexRender
                            v-if="!header.isPlaceholder"
                            :render="header.column.columnDef.header"
                            :props="header.getContext()"
                        />
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow
                    v-for="row in table.getRowModel().rows"
                    :key="row.id"
                    class="hover:bg-muted/50"
                >
                    <TableCell
                        v-for="cell in row.getVisibleCells()"
                        :key="cell.id"
                    >
                        <FlexRender
                            :render="cell.column.columnDef.cell"
                            :props="cell.getContext()"
                        />
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
