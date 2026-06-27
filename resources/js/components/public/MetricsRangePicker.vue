<script setup lang="ts">
import { CalendarDays } from "@lucide/vue";
import { ref, watch } from "vue";
import { Button } from "@/components/ui/button";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import type { MetricsRange } from "@/types/public";

const props = defineProps<{
    range: MetricsRange;
    from: string;
    to: string;
}>();

const emit = defineEmits<{
    change: [range: MetricsRange, from?: string, to?: string];
}>();

const open = ref(false);
const customFrom = ref(props.from);
const customTo = ref(props.to);

watch(
    () => [props.from, props.to] as const,
    ([f, t]) => {
        customFrom.value = f;
        customTo.value = t;
    },
);

const PRESETS: { label: string; value: Exclude<MetricsRange, "custom"> }[] = [
    { label: "1D", value: "1d" },
    { label: "7D", value: "7d" },
    { label: "30D", value: "30d" },
];

function selectPreset(value: Exclude<MetricsRange, "custom">): void {
    emit("change", value);
}

function applyCustom(): void {
    if (!customFrom.value || !customTo.value) return;

    emit("change", "custom", customFrom.value, customTo.value);
    open.value = false;
}
</script>

<template>
    <div class="flex items-center gap-2">
        <div class="border-border flex items-center rounded-md border p-0.5">
            <Button
                v-for="preset in PRESETS"
                :key="preset.value"
                :variant="range === preset.value ? 'default' : 'ghost'"
                size="sm"
                class="h-7 px-2.5"
                @click="selectPreset(preset.value)"
            >
                {{ preset.label }}
            </Button>
        </div>

        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    :variant="range === 'custom' ? 'default' : 'outline'"
                    size="sm"
                    class="h-8 gap-1.5"
                >
                    <CalendarDays class="size-3.5" />
                    <span class="text-xs">
                        {{
                            range === "custom"
                                ? `${from} → ${to}`
                                : "Custom range"
                        }}
                    </span>
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-68 p-4" align="end">
                <p class="text-sm font-medium mb-3">Custom date range</p>
                <div class="flex flex-col gap-3">
                    <div class="flex flex-col gap-1.5">
                        <label
                            class="text-xs text-muted-foreground font-medium"
                        >
                            From
                        </label>
                        <input
                            v-model="customFrom"
                            type="date"
                            class="border-input bg-background text-foreground focus:ring-ring h-8 w-full rounded-md border px-2 text-xs outline-none focus:ring-1"
                        />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label
                            class="text-xs text-muted-foreground font-medium"
                        >
                            To
                        </label>
                        <input
                            v-model="customTo"
                            type="date"
                            class="border-input bg-background text-foreground focus:ring-ring h-8 w-full rounded-md border px-2 text-xs outline-none focus:ring-1"
                        />
                    </div>
                    <Button size="sm" class="mt-1 w-full" @click="applyCustom">
                        Apply range
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    </div>
</template>
