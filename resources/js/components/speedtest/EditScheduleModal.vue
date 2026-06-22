<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { X } from "@lucide/vue";
import cronstrue from "cronstrue";
import { computed, ref } from "vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Separator } from "@/components/ui/separator";
import { Switch } from "@/components/ui/switch";
import type { ProviderSchedule, ProviderWithSchedules } from "@/types/provider";

const props = defineProps<{
    schedule: ProviderSchedule;
    provider: ProviderWithSchedules;
}>();

const emit = defineEmits<{
    close: [];
}>();

const cronSegments = ["minute", "hour", "day", "month", "weekday"] as const;

const presets = [
    { label: "Every 15 min", value: "*/15 * * * *" },
    { label: "Every 30 min", value: "*/30 * * * *" },
    { label: "Every hour", value: "0 * * * *" },
    { label: "Every 2 hours", value: "0 */2 * * *" },
    { label: "Every 6 hours", value: "0 */6 * * *" },
    { label: "Daily", value: "0 0 * * *" },
] as const;

const parseCron = (expr: string): string[] => {
    const parts = (expr || "* * * * *").split(" ");

    while (parts.length < 5) {
        parts.push("*");
    }

    return parts.slice(0, 5);
};

const form = useForm({
    label: props.schedule.label,
    cron_expression: props.schedule.cron_expression ?? "",
    is_enabled: props.schedule.is_enabled,
});

const cronParts = ref<string[]>(
    parseCron(props.schedule.cron_expression ?? ""),
);

const onSegmentInput = () => {
    form.cron_expression = cronParts.value
        .map((p) => p.trim() || "*")
        .join(" ");
};

const selectPreset = (value: string) => {
    form.cron_expression = value;
    cronParts.value = value.split(" ");
};

const previewLabel = computed(() => {
    if (!form.cron_expression) {
        return null;
    }

    try {
        return cronstrue.toString(form.cron_expression, {
            throwExceptionOnParseError: true,
            verbose: false,
        });
    } catch {
        return null;
    }
});

const isSubmitting = ref(false);

const handleSubmit = () => {
    isSubmitting.value = true;
    form.patch(
        route(
            "speedtest.schedules.update",
            { providerSchedule: props.schedule.id },
            false,
        ),
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false;
                emit("close");
            },
        },
    );
};
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/45 p-5"
        @click.self="$emit('close')"
    >
        <div
            class="w-full max-w-md rounded-lg border border-border bg-background shadow-lg"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-border px-6 py-4"
            >
                <div>
                    <h2 class="text-base font-medium text-foreground">
                        Edit schedule
                    </h2>
                    <p class="text-muted-foreground mt-0.5 text-xs">
                        {{ provider.label }}
                    </p>
                </div>
                <button
                    class="text-muted-foreground hover:text-foreground rounded p-1 transition-colors"
                    @click="$emit('close')"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- Body -->
            <div class="space-y-5 px-6 py-4">
                <!-- Schedule name -->
                <div>
                    <label
                        class="mb-2 block text-xs font-medium text-foreground"
                    >
                        Schedule name
                    </label>
                    <Input
                        v-model="form.label"
                        type="text"
                        class="text-sm"
                        :class="{ 'border-destructive': form.errors.label }"
                    />
                    <p
                        v-if="form.errors.label"
                        class="text-destructive mt-1 text-xs"
                    >
                        {{ form.errors.label }}
                    </p>
                </div>

                <Separator />

                <!-- Cron expression section -->
                <div>
                    <p
                        class="text-muted-foreground mb-3 text-xs font-medium uppercase tracking-wider"
                    >
                        Cron expression
                    </p>

                    <!-- Preset buttons -->
                    <div class="mb-4 flex flex-wrap gap-2">
                        <button
                            v-for="preset in presets"
                            :key="preset.value"
                            type="button"
                            class="rounded-md border border-border bg-transparent px-3 py-1.5 text-xs font-medium text-muted-foreground transition-all hover:border-muted-foreground/50 hover:text-foreground"
                            :class="
                                form.cron_expression === preset.value
                                    ? 'border-primary bg-primary/10 text-foreground'
                                    : ''
                            "
                            @click="selectPreset(preset.value)"
                        >
                            {{ preset.label }}
                        </button>
                    </div>

                    <p class="text-muted-foreground mb-2 text-xs font-medium">
                        Custom cron expression
                    </p>

                    <!-- 5-segment cron inputs -->
                    <div class="flex gap-1.5">
                        <template
                            v-for="(segment, index) in cronSegments"
                            :key="segment"
                        >
                            <div
                                class="flex flex-1 flex-col items-center gap-1"
                            >
                                <Input
                                    v-model="cronParts[index]"
                                    type="text"
                                    placeholder="*"
                                    class="text-center font-mono text-sm font-medium"
                                    @input="onSegmentInput"
                                />
                                <span class="text-muted-foreground text-[10px]">
                                    {{ segment }}
                                </span>
                            </div>
                            <span
                                v-if="index < 4"
                                class="text-muted-foreground flex items-center"
                            >
                                ·
                            </span>
                        </template>
                    </div>

                    <p
                        v-if="form.errors.cron_expression"
                        class="text-destructive mt-2 text-xs"
                    >
                        {{ form.errors.cron_expression }}
                    </p>

                    <!-- Preview -->
                    <div
                        class="mt-3 rounded-md border border-border bg-muted/30 px-3 py-2"
                    >
                        <p class="text-xs">
                            <span class="text-foreground font-medium">
                                Preview:
                            </span>
                            <template v-if="previewLabel">
                                <span class="text-foreground ml-1">
                                    {{ previewLabel }}
                                </span>
                            </template>
                            <template v-else>
                                <span class="text-muted-foreground">
                                    Pick a preset or enter a custom expression
                                </span>
                            </template>
                        </p>
                    </div>
                </div>

                <Separator />

                <!-- Enable toggle -->
                <div
                    class="flex items-center justify-between rounded-md bg-muted/30 px-3 py-2.5"
                >
                    <div>
                        <p class="text-sm font-medium text-foreground">
                            Enable this schedule
                        </p>
                        <p class="text-muted-foreground text-[11px]">
                            Schedule will run immediately when enabled
                        </p>
                    </div>
                    <Switch v-model="form.is_enabled" />
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex justify-end gap-2.5 border-t border-border px-6 py-3"
            >
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="$emit('close')"
                >
                    Cancel
                </Button>
                <Button
                    size="sm"
                    :disabled="isSubmitting"
                    @click="handleSubmit"
                >
                    {{ isSubmitting ? "Saving..." : "Save changes" }}
                </Button>
            </div>
        </div>
    </div>
</template>
