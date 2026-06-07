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
    METRIC_OPTIONS,
    OPERATOR_OPTIONS,
    STATUS_VALUES,
} from "@/types/alert-rule";
import type { AlertRuleCondition, AlertRuleOperator } from "@/types/alert-rule";

const props = defineProps<{
    condition: AlertRuleCondition;
    index: number;
}>();

const emit = defineEmits<{
    update: [condition: AlertRuleCondition];
    remove: [];
}>();

const selectedMetric = computed(() =>
    METRIC_OPTIONS.find((m) => m.value === props.condition.metric),
);

const isNumericMetric = computed(() => selectedMetric.value?.numeric ?? false);

// Filter operators based on metric type
const availableOperators = computed(() =>
    isNumericMetric.value
        ? OPERATOR_OPTIONS
        : OPERATOR_OPTIONS.filter((o) => !o.numericOnly),
);

const update = (key: keyof AlertRuleCondition, value: string) => {
    const updated = { ...props.condition, [key]: value };

    // Reset value when metric type changes
    if (key === "metric") {
        const newMetric = METRIC_OPTIONS.find((m) => m.value === value);

        if (
            newMetric?.numeric &&
            !["is_above", "is_below"].includes(updated.operator)
        ) {
            updated.operator = "is_above" as AlertRuleOperator;
        }

        if (
            !newMetric?.numeric &&
            ["is_above", "is_below"].includes(updated.operator)
        ) {
            updated.operator = "is" as AlertRuleOperator;
        }

        updated.value = newMetric?.numeric ? "" : "failed";
    }

    emit("update", updated);
};
</script>

<template>
    <div
        class="flex items-center gap-2 rounded-lg bg-primary/5 border border-primary/10 px-3 py-2.5 flex-wrap"
    >
        <!-- Condition number badge -->
        <div
            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/20 text-[10px] font-medium text-primary"
        >
            {{ index + 1 }}
        </div>

        <!-- Metric -->
        <Select
            :model-value="condition.metric"
            @update:model-value="(v) => v && update('metric', String(v))"
        >
            <SelectTrigger class="h-8 w-auto min-w-[140px] text-xs">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="opt in METRIC_OPTIONS"
                    :key="opt.value"
                    :value="opt.value"
                    class="text-xs"
                >
                    {{ opt.label }}
                </SelectItem>
            </SelectContent>
        </Select>

        <!-- Operator -->
        <Select
            :model-value="condition.operator"
            @update:model-value="(v) => v && update('operator', String(v))"
        >
            <SelectTrigger class="h-8 w-auto min-w-[100px] text-xs">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="opt in availableOperators"
                    :key="opt.value"
                    :value="opt.value"
                    class="text-xs"
                >
                    {{ opt.label }}
                </SelectItem>
            </SelectContent>
        </Select>

        <!-- Value — select for status, input for numeric -->
        <Select
            v-if="!isNumericMetric"
            :model-value="condition.value"
            @update:model-value="(v) => v && update('value', String(v))"
        >
            <SelectTrigger class="h-8 w-auto min-w-[100px] text-xs">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem
                    v-for="opt in STATUS_VALUES"
                    :key="opt.value"
                    :value="opt.value"
                    class="text-xs"
                >
                    {{ opt.label }}
                </SelectItem>
            </SelectContent>
        </Select>

        <div v-else class="flex items-center gap-1.5">
            <Input
                :model-value="condition.value"
                type="number"
                min="0"
                class="h-8 w-24 text-xs"
                @update:model-value="(v) => update('value', String(v))"
            />
            <span class="text-muted-foreground text-xs">
                {{
                    condition.metric === "ping_ms" ||
                    condition.metric === "jitter_ms"
                        ? "ms"
                        : condition.metric === "packet_loss"
                          ? "%"
                          : "Mbps"
                }}
            </span>
        </div>

        <!-- Remove button -->
        <Button
            variant="ghost"
            size="icon"
            class="text-muted-foreground hover:text-destructive ml-auto h-7 w-7 shrink-0"
            @click="emit('remove')"
        >
            <Trash2 class="h-3.5 w-3.5" />
        </Button>
    </div>
</template>
