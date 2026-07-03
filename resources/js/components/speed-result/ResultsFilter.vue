<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { Server } from "@lucide/vue";
import { ref, watch } from "vue";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import type {
    TProviderOption,
    TSpeedResultFilters,
} from "@/types/speed-result";

const props = defineProps<{
    providers: TProviderOption[];
    filters: TSpeedResultFilters;
    routeName: string;
}>();

const provider = ref<string>(props.filters.provider ?? "all");

watch(provider, (value) => {
    router.get(
        route(props.routeName),
        {
            ...props.filters,
            provider: value === "all" ? undefined : value,
            page: undefined,
        },
        { preserveScroll: true, preserveState: true, replace: true },
    );
});
</script>

<template>
    <Select v-model="provider">
        <SelectTrigger size="sm" class="h-8 w-auto gap-1.5 text-sm">
            <Server class="h-3.5 w-3.5 text-muted-foreground" />
            <SelectValue placeholder="All providers" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem value="all">All providers</SelectItem>
            <SelectItem v-for="p in providers" :key="p.slug" :value="p.slug">
                {{ p.name }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
