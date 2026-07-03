<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { CalendarRange, X } from "@lucide/vue";
import type { DateValue, DateRange } from "reka-ui";
import { computed, ref, watch } from "vue";
import DatePresetList from "@/components/speed-result/DatePresetList.vue";
import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import { RangeCalendar } from "@/components/ui/range-calendar";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { useDateRangePresets } from "@/composables/useDateRangePresets";
import type { TDatePreset } from "@/composables/useDateRangePresets";
import {
    formatDisplayDate,
    fromCalendarDate,
    toCalendarDate,
} from "@/lib/date";
import type { TSpeedResultFilters } from "@/types/speed-result";

const props = defineProps<{
    months: string[];
    filters: TSpeedResultFilters;
    routeName: string;
}>();

const open = ref(false);
const mode = ref<"single" | "range">(props.filters.date ? "single" : "range");
const singleDate = ref<DateValue | undefined>(
    toCalendarDate(props.filters.date) as DateValue | undefined,
);
const range = ref<DateRange | undefined>(
    buildRange(props.filters.date_from, props.filters.date_to) as
        | DateRange
        | undefined,
);

function buildRange(
    from: string | null,
    to: string | null,
): DateRange | undefined {
    const start = toCalendarDate(from);
    const end = toCalendarDate(to);

    return start && end ? { start, end } : undefined;
}

const { presets, match } = useDateRangePresets(computed(() => props.months));
const activePresetKey = computed(() =>
    match(props.filters.date_from, props.filters.date_to),
);

const isActive = computed(() =>
    Boolean(
        props.filters.date || props.filters.date_from || props.filters.date_to,
    ),
);

const triggerLabel = computed(() => {
    if (props.filters.date) return formatDisplayDate(props.filters.date);

    if (props.filters.date_from && props.filters.date_to) {
        return `${formatDisplayDate(props.filters.date_from)} – ${formatDisplayDate(props.filters.date_to)}`;
    }

    return "Date";
});

watch(
    () => props.filters,
    () => {
        singleDate.value = toCalendarDate(props.filters.date) as
            | DateValue
            | undefined;
        range.value = buildRange(
            props.filters.date_from,
            props.filters.date_to,
        ) as DateRange | undefined;
    },
);

function selectPreset(preset: TDatePreset): void {
    mode.value = "range";
    range.value = buildRange(preset.from, preset.to) as DateRange | undefined;
}

function navigate(payload: Record<string, string | undefined>): void {
    router.get(
        route(props.routeName),
        { ...props.filters, ...payload, page: undefined },
        { preserveScroll: true, preserveState: true, replace: true },
    );

    open.value = false;
}

function apply(): void {
    if (mode.value === "single") {
        navigate({
            date: fromCalendarDate(singleDate.value as any) ?? undefined,
            date_from: undefined,
            date_to: undefined,
        });

        return;
    }

    navigate({
        date: undefined,
        date_from: fromCalendarDate(range.value?.start as any) ?? undefined,
        date_to: fromCalendarDate(range.value?.end as any) ?? undefined,
    });
}

function clear(): void {
    navigate({ date: undefined, date_from: undefined, date_to: undefined });
}
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                size="sm"
                class="h-8 gap-1.5 text-sm"
                :class="isActive ? 'border-primary text-primary' : ''"
            >
                <CalendarRange class="h-3.5 w-3.5" />
                {{ triggerLabel }}
                <X
                    v-if="isActive"
                    class="h-3.5 w-3.5 opacity-70"
                    @click.stop="clear"
                />
            </Button>
        </PopoverTrigger>

        <PopoverContent class="w-85 p-0" align="start">
            <Tabs v-model="mode" class="w-full gap-0">
                <TabsList
                    class="h-auto w-full rounded-none border-b bg-transparent p-0"
                >
                    <TabsTrigger
                        value="single"
                        class="flex-1 rounded-none py-2 data-[state=active]:border-b-2 data-[state=active]:border-primary data-[state=active]:shadow-none"
                    >
                        Single day
                    </TabsTrigger>
                    <TabsTrigger
                        value="range"
                        class="flex-1 rounded-none py-2 data-[state=active]:border-b-2 data-[state=active]:border-primary data-[state=active]:shadow-none"
                    >
                        Range
                    </TabsTrigger>
                </TabsList>

                <div class="flex flex-col gap-3 p-3">
                    <TabsContent value="single" class="mt-0">
                        <Calendar v-model="singleDate as any" class="p-0" />
                    </TabsContent>

                    <TabsContent value="range" class="mt-0 flex flex-col gap-3">
                        <DatePresetList
                            :presets="presets"
                            :active-key="activePresetKey"
                            @select="selectPreset"
                        />
                        <RangeCalendar v-model="range as any" class="p-0" />
                    </TabsContent>

                    <div class="flex justify-end gap-2 border-t pt-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-7 text-xs"
                            @click="clear"
                            >Clear</Button
                        >
                        <Button size="sm" class="h-7 text-xs" @click="apply"
                            >Apply</Button
                        >
                    </div>
                </div>
            </Tabs>
        </PopoverContent>
    </Popover>
</template>
