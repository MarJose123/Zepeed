<script setup lang="ts">
import { Trash2 } from "@lucide/vue";
import { computed } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    PING_LOOKBACK_OPTIONS,
    PING_METRIC_OPTIONS,
    PING_OPERATOR_OPTIONS,
} from "@/types/ping";
import type {
    PingAlertCondition,
    PingAlertMetric,
    PingAlertOperator,
} from "@/types/ping";

const props = defineProps<{ condition: PingAlertCondition; index: number }>();

const emit = defineEmits<{
    update: [condition: PingAlertCondition];
    remove: [];
}>();

const selectedMetric = computed(() =>
    PING_METRIC_OPTIONS.find((m) => m.value === props.condition.metric),
);

const unit = computed(() => selectedMetric.value?.unit ?? "");

const update = (key: keyof PingAlertCondition, value: string | number) => {
    emit("update", { ...props.condition, [key]: value });
};
</script>

<template>
    <div
        class="flex flex-wrap items-center gap-2 rounded-lg border border-blue-100 bg-blue-50/50 px-3 py-2.5 dark:border-blue-900 dark:bg-blue-950/20"
    >
        <div
            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-[10px] font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300"
        >
            {{ index + 1 }}
        </div>

        <Select
            :model-value="condition.metric"
            @update:model-value="
                (v) => v && update('metric', v as PingAlertMetric)
            "
        >
            <SelectTrigger class="h-8 w-auto min-w-40 text-xs"
                ><SelectValue
            /></SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="opt in PING_METRIC_OPTIONS"
                    :key="opt.value"
                    :value="opt.value"
                    class="text-xs"
                >
                    {{ opt.label }}
                </SelectItem>
            </SelectContent>
        </Select>

        <Select
            :model-value="condition.operator"
            @update:model-value="
                (v) => v && update('operator', v as PingAlertOperator)
            "
        >
            <SelectTrigger class="h-8 w-auto min-w-36 text-xs"
                ><SelectValue
            /></SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="opt in PING_OPERATOR_OPTIONS"
                    :key="opt.value"
                    :value="opt.value"
                    class="text-xs"
                >
                    {{ opt.label }}
                </SelectItem>
            </SelectContent>
        </Select>

        <div class="flex items-center gap-1.5">
            <Input
                :model-value="condition.value"
                type="number"
                min="0"
                class="h-8 w-24 text-xs"
                @update:model-value="(v) => update('value', String(v))"
            />
            <span v-if="unit" class="text-xs text-muted-foreground">{{
                unit
            }}</span>
        </div>

        <div class="flex items-center gap-1.5">
            <span class="text-xs text-muted-foreground">over</span>
            <Select
                :model-value="String(condition.lookback_minutes)"
                @update:model-value="
                    (v) => v && update('lookback_minutes', Number(v))
                "
            >
                <SelectTrigger class="h-8 w-auto min-w-24 text-xs"
                    ><SelectValue
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="opt in PING_LOOKBACK_OPTIONS"
                        :key="opt.value"
                        :value="String(opt.value)"
                        class="text-xs"
                    >
                        {{ opt.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <Button
            variant="ghost"
            size="icon"
            class="ml-auto h-7 w-7 shrink-0 text-muted-foreground hover:text-destructive"
            @click="emit('remove')"
        >
            <Trash2 class="h-3.5 w-3.5" />
        </Button>
    </div>
</template>
