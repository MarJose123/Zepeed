<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { Button } from "@/components/ui/button";
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
    <div class="rounded-xl border border-border bg-card overflow-hidden">
        <div class="flex items-center gap-2 px-4 py-2.5 flex-wrap">
            <span
                class="font-mono text-[10px] uppercase tracking-widest text-muted-foreground"
            >
                Filter
            </span>

            <Select v-model="provider">
                <SelectTrigger class="h-8 w-[180px] font-mono text-xs">
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

            <Select v-model="month">
                <SelectTrigger class="h-8 w-[180px] font-mono text-xs">
                    <SelectValue placeholder="All Months" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Months</SelectItem>
                    <SelectItem v-for="m in months" :key="m" :value="m">
                        {{ formatMonth(m) }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Separator orientation="vertical" class="h-4" />

            <Select v-model="perPage">
                <SelectTrigger class="h-8 w-[110px] font-mono text-xs">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="10">10 / page</SelectItem>
                    <SelectItem value="25">25 / page</SelectItem>
                    <SelectItem value="50">50 / page</SelectItem>
                    <SelectItem value="100">100 / page</SelectItem>
                </SelectContent>
            </Select>

            <Button
                variant="ghost"
                size="sm"
                class="ml-auto font-mono text-xs text-muted-foreground"
                @click="reset"
            >
                Reset
            </Button>
        </div>
    </div>
</template>
