<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { FileJson, FileSpreadsheet, FileText, Download } from "@lucide/vue";
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/components/ui/dialog";
import type { ExportFormat } from "@/types/export";
import type { TSpeedResultFilters } from "@/types/speed-result";

const props = defineProps<{
    filters: TSpeedResultFilters;
    routeName: string;
    moduleLabel: string;
}>();

const open = defineModel<boolean>("open", { default: false });

const exportRouteMap: Record<string, string> = {
    "speedtest.results.download": "speedtest.results.download.export",
    "speedtest.results.upload": "speedtest.results.upload.export",
    "speedtest.results.latency": "speedtest.results.latency.export",
};

const form = useForm({
    format: "csv" as ExportFormat,
    provider: props.filters.provider ?? null,
    date_from: props.filters.date_from ?? null,
    date_to: props.filters.date_to ?? null,
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

const hasDateRange = ref(
    Boolean(props.filters.date_from && props.filters.date_to),
);

function submit(): void {
    const exportRoute = exportRouteMap[props.routeName];

    if (!exportRoute) return;

    form.post(route(exportRoute), {
        onSuccess: () => {
            open.value = false;
            form.reset();
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
                <!-- Format selector -->
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

                <!-- Applied filters read-only -->
                <div class="flex flex-col gap-2">
                    <span class="text-sm font-medium">Applied filters</span>
                    <div v-if="hasDateRange" class="flex flex-wrap gap-1.5">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-border bg-secondary px-2.5 py-1 text-[11px] text-secondary-foreground"
                        >
                            {{ filters.date_from }} – {{ filters.date_to }}
                        </span>
                        <span
                            v-if="filters.provider"
                            class="inline-flex items-center gap-1.5 rounded-full border border-border bg-secondary px-2.5 py-1 text-[11px] text-secondary-foreground"
                        >
                            {{ filters.provider }}
                        </span>
                    </div>
                    <p
                        v-if="!hasDateRange"
                        class="text-[11px] text-destructive"
                    >
                        A date range is required. Set one via the date filter on
                        this page before exporting.
                    </p>
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

                <!-- Validation errors -->
                <p
                    v-if="form.errors.date_from || form.errors.date_to"
                    class="text-[11px] text-destructive"
                >
                    {{ form.errors.date_from ?? form.errors.date_to }}
                </p>
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
