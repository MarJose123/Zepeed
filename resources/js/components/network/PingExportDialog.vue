<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import {
    FileJson,
    FileSpreadsheet,
    FileText,
    Download,
    CalendarRange,
} from "@lucide/vue";
import type { DateRange } from "reka-ui";
import { ref, computed } from "vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
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
import { formatDisplayDate, fromCalendarDate } from "@/lib/date";
import type { ExportFormat } from "@/types/export";
import type { PingTarget } from "@/types/ping";

defineProps<{
    targets: PingTarget[];
}>();

const open = defineModel<boolean>("open", { default: false });

const calOpen = ref(false);
const dateRange = ref<DateRange | undefined>();

const form = useForm({
    format: "csv" as ExportFormat,
    target: null as string | null,
    date_from: null as string | null,
    date_to: null as string | null,
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

const triggerLabel = computed(() => {
    if (form.date_from && form.date_to) {
        return `${formatDisplayDate(form.date_from)} – ${formatDisplayDate(form.date_to)}`;
    }

    return "Select date range";
});

function onRangeSelect(range: DateRange | undefined): void {
    dateRange.value = range;

    if (range?.start && range?.end) {
        form.date_from = fromCalendarDate(range.start as any);
        form.date_to = fromCalendarDate(range.end as any);
        calOpen.value = false;
    }
}

function submit(): void {
    form.post(route("speedtest.network.ping-results.export"), {
        onSuccess: () => {
            open.value = false;
            dateRange.value = undefined;
            form.reset();
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-[440px]">
            <DialogHeader>
                <DialogTitle>Export Ping Results</DialogTitle>
                <DialogDescription>
                    Choose a date range and format. This won't affect the 24h /
                    7d / 30d view on the page.
                </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col gap-4">
                <!-- Format -->
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
                                    ? 'border-primary bg-primary/8 text-primary'
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
                                    !form.date_from
                                        ? 'text-muted-foreground'
                                        : ''
                                "
                            >
                                <CalendarRange class="size-3.5" />
                                {{ triggerLabel }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-3" align="start">
                            <RangeCalendar
                                v-model="dateRange as any"
                                :number-of-months="1"
                                class="p-0"
                                @update:model-value="onRangeSelect"
                            />
                        </PopoverContent>
                    </Popover>
                    <p
                        v-if="form.errors.date_from || form.errors.date_to"
                        class="text-[11px] text-destructive"
                    >
                        {{ form.errors.date_from ?? form.errors.date_to }}
                    </p>
                </div>

                <!-- Target filter -->
                <div class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Target</span>
                    <Select v-model="form.target">
                        <SelectTrigger class="h-9 text-sm">
                            <SelectValue placeholder="All targets" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All targets</SelectItem>
                            <SelectItem
                                v-for="t in targets"
                                :key="t.id"
                                :value="t.id"
                            >
                                {{ t.label }} ({{ t.host }})
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Info -->
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
                    :disabled="
                        form.processing || !form.date_from || !form.date_to
                    "
                    @click="submit"
                >
                    <Download class="size-3.5" />
                    Generate export
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
