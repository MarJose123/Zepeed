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
    <div>
        <!-- Toolbar -->
        <div v-if="$slots.toolbar" class="flex items-center justify-end py-4">
            <slot name="toolbar" />
        </div>

        <!-- Table -->
        <div class="rounded-md border">
            <Table class="table-fixed w-full">
                <TableHeader>
                    <TableRow
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                    >
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
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
                            :data-state="
                                row.getIsSelected() ? 'selected' : undefined
                            "
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
                            class="h-24 text-center"
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
                    results.meta.from ?? 0
                }}</span>
                –
                <span class="font-medium text-foreground">{{
                    results.meta.to ?? 0
                }}</span>
                of
                <span class="font-medium text-foreground">{{
                    results.meta.total
                }}</span>
                results
            </p>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!results.links.prev"
                >
                    <Link
                        v-if="results.links.prev"
                        :href="results.links.prev"
                        preserve-scroll
                    >
                        Previous
                    </Link>
                    <span v-else>Previous</span>
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!results.links.next"
                >
                    <Link
                        v-if="results.links.next"
                        :href="results.links.next"
                        preserve-scroll
                    >
                        Next
                    </Link>
                    <span v-else>Next</span>
                </Button>
            </div>
        </div>
    </div>
</template>
