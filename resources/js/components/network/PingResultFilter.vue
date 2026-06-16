<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { ListFilter } from "@lucide/vue";
import { computed, ref, watch } from "vue";
import { Button } from "@/components/ui/button";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";
import type { PingResultFilters, PingTarget } from "@/types/ping";

const props = defineProps<{
    targets: PingTarget[];
    filters: PingResultFilters;
}>();

const range = ref<string>(props.filters.range ?? "24h");
const target = ref<string>(props.filters.target ?? "all");
const status = ref<string>(props.filters.status ?? "all");
const perPage = ref<string>(String(props.filters.per_page ?? 25));

const activeCount = computed(
    () =>
        (range.value !== "24h" ? 1 : 0) +
        (target.value !== "all" ? 1 : 0) +
        (status.value !== "all" ? 1 : 0),
);
const hasActiveFilters = computed(() => activeCount.value > 0);

const apply = () => {
    router.get(
        route("speedtest.network.ping-results.index"),
        {
            range: range.value,
            target: target.value !== "all" ? target.value : undefined,
            status: status.value !== "all" ? status.value : undefined,
            per_page: perPage.value,
        },
        { preserveScroll: true, replace: true },
    );
};

const reset = () => {
    range.value = "24h";
    target.value = "all";
    status.value = "all";
    perPage.value = "25";
    router.get(
        route("speedtest.network.ping-results.index"),
        {},
        { replace: true },
    );
};

watch([range, target, status, perPage], apply);
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                size="sm"
                class="h-8 gap-2"
                :class="hasActiveFilters ? 'border-primary text-primary' : ''"
            >
                <ListFilter class="h-3.5 w-3.5" />
                Filters
                <span
                    v-if="hasActiveFilters"
                    class="flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[9px] font-medium text-primary-foreground"
                >
                    {{ activeCount }}
                </span>
            </Button>
        </PopoverTrigger>

        <PopoverContent class="w-64 p-4" align="end">
            <div class="space-y-4">
                <div class="space-y-1.5">
                    <p
                        class="text-xs uppercase tracking-[0.08em] text-muted-foreground"
                    >
                        Date Range
                    </p>
                    <Select v-model="range">
                        <SelectTrigger class="h-8 w-full text-sm"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="24h">Last 24 hours</SelectItem>
                            <SelectItem value="7d">Last 7 days</SelectItem>
                            <SelectItem value="30d">Last 30 days</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-1.5">
                    <p
                        class="text-xs uppercase tracking-[0.08em] text-muted-foreground"
                    >
                        Target
                    </p>
                    <Select v-model="target">
                        <SelectTrigger class="h-8 w-full text-sm"
                            ><SelectValue placeholder="All Targets"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Targets</SelectItem>
                            <SelectItem
                                v-for="t in targets"
                                :key="t.id"
                                :value="t.id"
                                >{{ t.label }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-1.5">
                    <p
                        class="text-xs uppercase tracking-[0.08em] text-muted-foreground"
                    >
                        Status
                    </p>
                    <Select v-model="status">
                        <SelectTrigger class="h-8 w-full text-sm"
                            ><SelectValue placeholder="All Statuses"
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            <SelectItem value="success">Success</SelectItem>
                            <SelectItem value="partial">Partial</SelectItem>
                            <SelectItem value="failed">Failed</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-1.5">
                    <p
                        class="text-xs uppercase tracking-[0.08em] text-muted-foreground"
                    >
                        Rows per page
                    </p>
                    <Select v-model="perPage">
                        <SelectTrigger class="h-8 w-full text-sm"
                            ><SelectValue
                        /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="10">10 / page</SelectItem>
                            <SelectItem value="25">25 / page</SelectItem>
                            <SelectItem value="50">50 / page</SelectItem>
                            <SelectItem value="100">100 / page</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <Separator />

                <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 w-full text-sm text-muted-foreground"
                    :disabled="!hasActiveFilters"
                    @click="reset"
                >
                    Reset filters
                </Button>
            </div>
        </PopoverContent>
    </Popover>
</template>
