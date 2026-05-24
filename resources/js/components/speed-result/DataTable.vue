<script setup lang="ts" generic="TData, TValue">
import { Link } from "@inertiajs/vue3";
import type { ColumnDef, SortingState } from "@tanstack/vue-table";
import {
    FlexRender,
    getCoreRowModel,
    getSortedRowModel,
    useVueTable,
} from "@tanstack/vue-table";
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { valueUpdater } from "@/components/ui/table/utils";
import type { TPagedResource } from "@/types";

const props = defineProps<{
    columns: ColumnDef<TData, TValue>[];
    results: TPagedResource<TData>;
}>();

const sorting = ref<SortingState>([]);

const table = useVueTable({
    get data() {
        return props.results.data;
    },
    get columns() {
        return props.columns;
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    manualPagination: true,
    onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sorting),
    state: {
        get sorting() {
            return sorting.value;
        },
    },
});
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
                <TableRow
                    v-for="headerGroup in table.getHeaderGroups()"
                    :key="headerGroup.id"
                    class="hover:bg-transparent border-border"
                >
                    <TableHead
                        v-for="header in headerGroup.headers"
                        :key="header.id"
                        class="font-mono text-[10px] uppercase tracking-[0.07em]"
                        :class="header.column.columnDef.meta?.headerClass"
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
                    <TableRow
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        class="hover:bg-transparent border-border"
                    >
                        <TableCell
                            v-for="cell in row.getVisibleCells()"
                            :key="cell.id"
                            :class="cell.column.columnDef.meta?.cellClass"
                        >
                            <FlexRender
                                :render="cell.column.columnDef.cell"
                                :props="cell.getContext()"
                            />
                        </TableCell>
                    </TableRow>
                </template>

                <TableRow v-else>
                    <TableCell
                        :colspan="columns.length"
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
