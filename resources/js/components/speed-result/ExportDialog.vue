<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import {
    CalendarRange,
    Download,
    FileJson,
    FileSpreadsheet,
    FileText,
    X,
} from "@lucide/vue";
import type { DateRange } from "reka-ui";
import { computed, ref } from "vue";
import DatePresetList from "@/components/speed-result/DatePresetList.vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import { RangeCalendar } from "@/components/ui/range-calendar";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { useDateRangePresets } from "@/composables/useDateRangePresets";
import {
    formatDisplayDate,
    fromCalendarDate,
    toCalendarDate,
} from "@/lib/date";
import type { ExportFormat } from "@/types/export";
import type {
    TProviderOption,
    TSpeedResultFilters,
} from "@/types/speed-result";

const props = defineProps<{
    filters: TSpeedResultFilters;
    months: string[];
    providers: TProviderOption[];
    routeName: string;
    moduleLabel: string;
}>();

const open = defineModel<boolean>("open", { default: false });

const exportRouteMap: Record<string, string> = {
    "speedtest.results.download": "speedtest.results.download.export",
    "speedtest.results.upload": "speedtest.results.upload.export",
    "speedtest.results.latency": "speedtest.results.latency.export",
};

const calOpen = ref(false);

const range = ref<DateRange | undefined>(
    props.filters.date_from && props.filters.date_to
        ? {
              start: toCalendarDate(props.filters.date_from),
              end: toCalendarDate(props.filters.date_to),
          }
        : undefined,
);

const form = useForm({
    format: "csv" as ExportFormat,
    provider: props.filters.provider ?? (null as string | null),
    date_from: props.filters.date_from ?? (null as string | null),
    date_to: props.filters.date_to ?? (null as string | null),
});

const formats: {
    value: ExportFormat;
    label: string;
    icon: typeof FileText;
    hint: string;
}[] = [
    { value: "csv", label: "CSV", icon: FileText, hint: "Spreadsheet-ready" },
    {
        value: "xlsx",
        label: "XLSX",
        icon: FileSpreadsheet,
        hint: "Excel workbook",
    },
    { value: "json", label: "JSON", icon: FileJson, hint: "Machine-readable" },
];

const { presets, match } = useDateRangePresets(computed(() => props.months));
const activePresetKey = computed(() => match(form.date_from, form.date_to));
const hasDateRange = computed(() => Boolean(form.date_from && form.date_to));

const triggerLabel = computed(() => {
    if (form.date_from && form.date_to) {
        return `${formatDisplayDate(form.date_from)} – ${formatDisplayDate(form.date_to)}`;
    }

    return "Select date range";
});

function onRangeChange(val: DateRange | undefined): void {
    range.value = val;
    form.date_from = fromCalendarDate(val?.start as any);
    form.date_to = fromCalendarDate(val?.end as any);

    if (val?.start && val?.end) calOpen.value = false;
}

function selectPreset(preset: { from: string; to: string }): void {
    form.date_from = preset.from;
    form.date_to = preset.to;
    range.value = {
        start: toCalendarDate(preset.from),
        end: toCalendarDate(preset.to),
    };
}

function clearRange(): void {
    range.value = undefined;
    form.date_from = null;
    form.date_to = null;
}

function submit(): void {
    const exportRoute = exportRouteMap[props.routeName];

    if (!exportRoute) return;

    form.post(route(exportRoute), {
        onSuccess: () => {
            open.value = false;
            form.reset();
            range.value = undefined;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-[440px]">
            <DialogHeader>
                <DialogTitle>Export {{ moduleLabel }}</DialogTitle>
                <DialogDescription>
                    Generates a file in the background and notifies you here
                    when it's ready to download.
                </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col gap-4">
                <!-- Format cards -->
                <div class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Format</span>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="f in formats"
                            :key="f.value"
                            type="button"
                            class="flex flex-col items-center gap-1.5 rounded-md border px-2 py-2.5 transition-colors"
                            :class="
                                form.format === f.value
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-border text-muted-foreground hover:border-primary/50'
                            "
                            @click="form.format = f.value"
                        >
                            <component :is="f.icon" class="size-4" />
                            <span class="text-sm font-medium">{{
                                f.label
                            }}</span>
                            <span class="text-[11px]">{{ f.hint }}</span>
                        </button>
                    </div>
                </div>

                <!-- Date range picker -->
                <div class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Date range</span>
                    <Popover v-model:open="calOpen">
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-9 w-full justify-start gap-2 text-sm font-normal"
                                :class="
                                    hasDateRange
                                        ? 'border-primary text-primary'
                                        : 'text-muted-foreground'
                                "
                            >
                                <CalendarRange class="size-3.5 shrink-0" />
                                {{ triggerLabel }}
                                <X
                                    v-if="hasDateRange"
                                    class="ml-auto size-3.5 opacity-60"
                                    @click.stop="clearRange"
                                />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="z-80 w-auto p-0" align="start">
                            <div class="flex flex-col gap-3 p-3">
                                <DatePresetList
                                    :presets="presets"
                                    :active-key="activePresetKey"
                                    @select="selectPreset"
                                />
                                <RangeCalendar
                                    v-model="range as any"
                                    :number-of-months="1"
                                    class="p-0"
                                    @update:model-value="onRangeChange"
                                />
                            </div>
                        </PopoverContent>
                    </Popover>
                    <p
                        v-if="form.errors.date_from || form.errors.date_to"
                        class="text-[11px] text-destructive"
                    >
                        {{ form.errors.date_from ?? form.errors.date_to }}
                    </p>
                </div>

                <!-- Provider filter -->
                <div class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Provider</span>
                    <Select v-model="form.provider">
                        <SelectTrigger class="h-9 text-sm">
                            <SelectValue placeholder="All providers" />
                        </SelectTrigger>
                        <SelectContent class="z-80">
                            <SelectItem value="">All providers</SelectItem>
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

                <!-- Info note -->
                <div
                    class="flex gap-2 rounded-md bg-muted px-3 py-2.5 text-[11px] text-muted-foreground"
                >
                    <Download class="mt-0.5 size-3.5 shrink-0 text-primary" />
                    <span
                        >Large ranges run as a background job. Files are kept
                        for 7 days.</span
                    >
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" size="sm" @click="open = false"
                    >Cancel</Button
                >
                <Button
                    size="sm"
                    :disabled="form.processing || !hasDateRange"
                    @click="submit"
                >
                    <Download class="size-3.5" />
                    Generate export
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
