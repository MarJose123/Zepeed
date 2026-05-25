<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { ListFilter } from "lucide-vue-next";
import { ref, watch, computed } from "vue";
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
import type {
    TProviderOption,
    TSpeedResultFilters,
} from "@/types/speed-result";

const props = defineProps<{
    providers: TProviderOption[];
    months: string[];
    filters: TSpeedResultFilters;
    routeName: string;
}>();

const provider = ref<string>(props.filters.provider ?? "all");
const month = ref<string>(props.filters.month ?? "all");
const perPage = ref<string>(String(props.filters.per_page));

const hasActiveFilters = computed(
    () => provider.value !== "all" || month.value !== "all",
);

function formatMonth(value: string): string {
    const [year, mon] = value.split("-");

    return new Date(Number(year), Number(mon) - 1).toLocaleString("default", {
        month: "long",
        year: "numeric",
    });
}

function apply(): void {
    router.get(
        route(props.routeName),
        {
            provider: provider.value === "all" ? undefined : provider.value,
            month: month.value === "all" ? undefined : month.value,
            per_page: perPage.value,
        },
        { preserveScroll: true, replace: true },
    );
}

function reset(): void {
    provider.value = "all";
    month.value = "all";
    perPage.value = "25";
    router.get(route(props.routeName), {}, { replace: true });
}

watch([provider, month, perPage], apply);
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                size="sm"
                class="h-8 gap-2 font-mono text-xs"
                :class="hasActiveFilters ? 'border-primary text-primary' : ''"
            >
                <ListFilter class="h-3.5 w-3.5" />
                Filter
                <span
                    v-if="hasActiveFilters"
                    class="flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[9px] font-semibold text-primary-foreground"
                >
                    {{
                        (provider !== "all" ? 1 : 0) + (month !== "all" ? 1 : 0)
                    }}
                </span>
            </Button>
        </PopoverTrigger>

        <PopoverContent class="w-64 p-4" align="end">
            <div class="space-y-4">
                <div class="space-y-1.5">
                    <p
                        class="font-mono text-[10px] uppercase tracking-[0.08em] text-muted-foreground"
                    >
                        Provider
                    </p>
                    <Select v-model="provider">
                        <SelectTrigger class="h-8 w-full font-mono text-xs">
                            <SelectValue placeholder="All Providers" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Providers</SelectItem>
                            <SelectItem
                                v-for="p in providers"
                                :key="p.slug"
                                :value="p.slug"
                            >
                                {{ p.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-1.5">
                    <p
                        class="font-mono text-[10px] uppercase tracking-[0.08em] text-muted-foreground"
                    >
                        Month
                    </p>
                    <Select v-model="month">
                        <SelectTrigger class="h-8 w-full font-mono text-xs">
                            <SelectValue placeholder="All Months" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Months</SelectItem>
                            <SelectItem v-for="m in months" :key="m" :value="m">
                                {{ formatMonth(m) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-1.5">
                    <p
                        class="font-mono text-[10px] uppercase tracking-[0.08em] text-muted-foreground"
                    >
                        Rows per page
                    </p>
                    <Select v-model="perPage">
                        <SelectTrigger class="h-8 w-full font-mono text-xs">
                            <SelectValue />
                        </SelectTrigger>
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
                    class="w-full h-8 font-mono text-xs text-muted-foreground"
                    :disabled="!hasActiveFilters"
                    @click="reset"
                >
                    Reset filters
                </Button>
            </div>
        </PopoverContent>
    </Popover>
</template>
