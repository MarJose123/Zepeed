<script setup lang="ts" generic="TData, TValue">
import { router } from "@inertiajs/vue3";
import { Inbox } from "@lucide/vue";
import type { ColumnDef } from "@tanstack/vue-table";
import { FlexRender, getCoreRowModel, useVueTable } from "@tanstack/vue-table";
import { onMounted, onUnmounted, ref } from "vue";
import ResultsPagination from "@/components/speed-result/ResultsPagination.vue";
import SortableTableHead from "@/components/speed-result/SortableTableHead.vue";
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@/components/ui/empty";
import { Spinner } from "@/components/ui/spinner";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import type { TPagedResource } from "@/types";
import type {
    TSpeedResultFilters,
    TSpeedResultSortKey,
} from "@/types/speed-result";

const props = defineProps<{
    columns: ColumnDef<TData, TValue>[];
    results: TPagedResource<TData>;
    filters: TSpeedResultFilters;
    routeName: string;
}>();

const isNavigating = ref(false);
let stopStart: (() => void) | undefined;
let stopFinish: (() => void) | undefined;

onMounted(() => {
    stopStart = router.on("start", () => {
        isNavigating.value = true;
    });
    stopFinish = router.on("finish", () => {
        isNavigating.value = false;
    });
});

onUnmounted(() => {
    stopStart?.();
    stopFinish?.();
});

const table = useVueTable({
    get data() {
        return props.results.data;
    },
    get columns() {
        return props.columns;
    },
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    manualSorting: true,
});

function toggleSort(sortKey: TSpeedResultSortKey): void {
    let nextSort: TSpeedResultSortKey | null = sortKey;
    let nextDirection: "asc" | "desc" | null = "asc";

    if (props.filters.sort === sortKey) {
        nextDirection = props.filters.direction === "asc" ? "desc" : null;
        nextSort = nextDirection === null ? null : sortKey;
    }

    router.get(
        route(props.routeName),
        {
            ...props.filters,
            sort: nextSort ?? undefined,
            direction: nextDirection ?? undefined,
            page: undefined,
        },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}
</script>

<template>
    <div>
        <div v-if="$slots.toolbar" class="flex items-center gap-2 py-4">
            <slot name="toolbar" />
        </div>

        <div class="relative rounded-md border">
            <div
                v-if="isNavigating"
                class="absolute right-4 top-3 z-10 flex items-center gap-2 text-xs text-muted-foreground"
            >
                <Spinner class="size-3.5" /> Updating…
            </div>

            <Table
                :class="
                    isNavigating
                        ? 'pointer-events-none opacity-50 transition-opacity'
                        : 'transition-opacity'
                "
            >
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
                            <SortableTableHead
                                v-if="header.column.columnDef.meta?.sortable"
                                :label="
                                    header.column.columnDef.meta.sortLabel ??
                                    String(header.column.columnDef.header)
                                "
                                :sort-key="
                                    header.column.columnDef.meta.sortKey!
                                "
                                :current-sort="filters.sort"
                                :current-direction="filters.direction"
                                :align="
                                    header.column.columnDef.meta.headerClass?.includes(
                                        'text-right',
                                    )
                                        ? 'right'
                                        : 'left'
                                "
                                @toggle="toggleSort"
                            />
                            <FlexRender
                                v-else-if="!header.isPlaceholder"
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
                        <TableCell :colspan="columns.length" class="h-64 p-0">
                            <Empty class="border-none">
                                <EmptyHeader>
                                    <EmptyMedia variant="icon"
                                        ><Inbox class="size-6"
                                    /></EmptyMedia>
                                    <EmptyTitle>No results found</EmptyTitle>
                                    <EmptyDescription>
                                        Try adjusting the provider or date
                                        filters to see more results.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <ResultsPagination
                :meta="results.meta"
                :filters="filters"
                :route-name="routeName"
            />
        </div>
    </div>
</template>
