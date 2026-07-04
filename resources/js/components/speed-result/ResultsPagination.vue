<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { computed } from "vue";
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from "@/components/ui/pagination";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import type { TPagedMeta } from "@/types";
import type { TSpeedResultFilters } from "@/types/speed-result";

const props = defineProps<{
    meta: TPagedMeta;
    filters: TSpeedResultFilters;
    routeName: string;
}>();

function navigate(payload: Record<string, string | number | undefined>): void {
    router.get(
        route(props.routeName),
        { ...props.filters, ...payload },
        { preserveScroll: true, preserveState: true, replace: true },
    );
}

const currentPage = computed<number>({
    get: () => props.meta.current_page,
    set: (page) => navigate({ page }),
});

const perPage = computed<string>({
    get: () => String(props.filters.per_page),
    set: (value) => navigate({ per_page: value, page: undefined }),
});
</script>

<template>
    <div class="grid grid-cols-3 items-center gap-3 border-t px-4 py-3">
        <div class="flex items-center gap-2">
            <p class="text-[11px] text-muted-foreground">Rows per page</p>
            <Select v-model="perPage">
                <SelectTrigger size="sm" class="h-7 w-17.5 text-xs">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="10">10</SelectItem>
                    <SelectItem value="25">25</SelectItem>
                    <SelectItem value="50">50</SelectItem>
                    <SelectItem value="100">100</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <p class="justify-self-center text-[11px] text-muted-foreground">
            Showing
            <span class="font-medium text-foreground">{{
                meta.from ?? 0
            }}</span>
            –
            <span class="font-medium text-foreground">{{ meta.to ?? 0 }}</span>
            of
            <span class="font-medium text-foreground">{{ meta.total }}</span>
            results
        </p>

        <div class="justify-self-end">
            <Pagination
                v-model:page="currentPage"
                :total="meta.total"
                :items-per-page="meta.per_page"
                :sibling-count="1"
                show-edges
            >
                <PaginationContent v-slot="{ items }">
                    <PaginationPrevious />
                    <template
                        v-for="item in items"
                        :key="item.type === 'page' ? item.value : item.type"
                    >
                        <PaginationItem
                            v-if="item.type === 'page'"
                            :value="item.value"
                            :is-active="item.value === currentPage"
                        >
                            {{ item.value }}
                        </PaginationItem>
                        <PaginationEllipsis v-else />
                    </template>
                    <PaginationNext />
                </PaginationContent>
            </Pagination>
        </div>
    </div>
</template>
