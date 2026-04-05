<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import cronstrue from "cronstrue";
import { ChevronRight, Loader2, Clock } from "lucide-vue-next";
import { computed, ref } from "vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Separator } from "@/components/ui/separator";
import { Switch } from "@/components/ui/switch";
import {
    Tooltip,
    TooltipTrigger,
    TooltipContent,
} from "@/components/ui/tooltip";
import type { ProviderSchedule } from "@/types/provider";

const props = defineProps<{
    schedule: ProviderSchedule;
    defaultOpen?: boolean;
}>();

const cronSegments = ["minute", "hour", "day", "month", "weekday"] as const;

const presets = [
    { label: "Every 15 min", value: "*/15 * * * *" },
    { label: "Every 30 min", value: "*/30 * * * *" },
    { label: "Every hour", value: "0 * * * *" },
    { label: "Every 2 hours", value: "0 */2 * * *" },
    { label: "Every 6 hours", value: "0 */6 * * *" },
    { label: "Daily at midnight", value: "0 0 * * *" },
] as const;

const descriptions: Record<string, string> = {
    "*/15 * * * *": "Every 15 minutes",
    "*/30 * * * *": "Every 30 minutes",
    "0 * * * *": "Every hour",
    "0 */2 * * *": "Every 2 hours",
    "0 */6 * * *": "Every 6 hours",
    "0 0 * * *": "Every day at midnight",
};

const form = useForm({
    cron_expression: props.schedule.cron_expression ?? "",
    is_enabled: props.schedule.is_enabled,
});

const parseCron = (expr: string): string[] => {
    const parts = (expr || "* * * * *").split(" ");

    while (parts.length < 5) {
        parts.push("*");
    }

    return parts.slice(0, 5);
};

const cronParts = ref<string[]>(parseCron(form.cron_expression));

const onSegmentInput = () => {
    form.cron_expression = cronParts.value
        .map((p) => p.trim() || "*")
        .join(" ");
};

const selectPreset = (value: string) => {
    form.cron_expression = value;
    cronParts.value = parseCron(value);
};

const previewLabel = computed(() => {
    if (!form.cron_expression) {
        return null;
    }

    // Check preset descriptions first for clean labels
    const preset = descriptions[form.cron_expression];

    if (preset) {
        return preset;
    }

    // Fall back to cronstrue for custom expressions
    try {
        return cronstrue.toString(form.cron_expression, {
            throwExceptionOnParseError: true,
            verbose: false,
        });
    } catch {
        return null;
    }
});

const faviconError = ref(false);

const onFaviconError = () => {
    faviconError.value = true;
};

const isOpen = ref(props.defaultOpen ?? false);

const toggleRow = () => {
    isOpen.value = !isOpen.value;
};

const cancel = () => {
    isOpen.value = false;
    form.cron_expression = props.schedule.cron_expression ?? "";
    form.is_enabled = props.schedule.is_enabled;
    cronParts.value = parseCron(form.cron_expression);
    form.clearErrors();
};

const save = () => {
    form.cron_expression = cronParts.value
        .map((p) => p.trim() || "*")
        .join(" ");

    form.patch(
        route(
            "speedtest.schedules.update",
            { providerSchedule: props.schedule.id },
            false,
        ),
        {
            preserveScroll: true,
            onSuccess: () => {
                isOpen.value = false;
                router.reload();
            },
        },
    );
};

const toggleEnabled = (val: boolean) => {
    form.is_enabled = val;

    router.patch(
        route(
            "speedtest.schedules.update",
            { providerSchedule: props.schedule.id },
            false,
        ),
        { is_enabled: val },
        { preserveScroll: true },
    );
};
</script>

<template>
    <div>
        <Separator />

        <!-- Row header -->
        <div
            class="flex items-center gap-3 px-4 py-3.5 transition-colors"
            :class="
                isOpen ? 'cursor-default' : 'cursor-pointer hover:bg-muted/50'
            "
            @click="toggleRow"
        >
            <!-- Favicon with Clock lucide fallback -->
            <div
                class="bg-muted border-border flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-md border"
            >
                <img
                    v-if="!faviconError"
                    :src="`https://www.google.com/s2/favicons?domain=${schedule.website_link}&sz=32`"
                    :alt="schedule.provider_name"
                    class="h-4 w-4 object-contain"
                    @error="onFaviconError"
                />
                <Clock v-else class="text-muted-foreground h-4 w-4" />
            </div>

            <!-- Provider info -->
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium">
                        {{ schedule.provider_name }}
                    </span>
                    <Badge
                        class="text-[10px]"
                        :class="
                            form.is_enabled
                                ? 'border-green-600/20 bg-green-50 text-green-700 dark:border-green-400/20 dark:bg-green-950 dark:text-green-400'
                                : 'border-border bg-muted text-muted-foreground'
                        "
                        variant="outline"
                    >
                        {{ form.is_enabled ? "enabled" : "disabled" }}
                    </Badge>
                </div>
                <p class="text-muted-foreground mt-0.5 text-xs">
                    <template v-if="form.cron_expression && previewLabel">
                        {{ previewLabel }}
                    </template>
                    <template v-else-if="form.cron_expression">
                        <code class="font-mono">{{
                            form.cron_expression
                        }}</code>
                    </template>
                    <template v-else>No schedule set</template>
                    <span
                        v-if="schedule.next_run_at && form.is_enabled"
                        class="ml-1"
                    >
                        · Next run {{ schedule.next_run_at }}
                    </span>
                </p>
            </div>

            <!-- Toggle -->
            <div @click.stop>
                <Tooltip v-if="!schedule.provider_is_enabled">
                    <TooltipTrigger as-child>
                        <span class="cursor-not-allowed">
                            <Switch
                                :model-value="form.is_enabled"
                                :disabled="true"
                                class="pointer-events-none"
                            />
                        </span>
                    </TooltipTrigger>
                    <TooltipContent side="left">
                        Enable this provider in Speedtest Providers first
                    </TooltipContent>
                </Tooltip>
                <Switch
                    v-else
                    :model-value="form.is_enabled"
                    @update:model-value="toggleEnabled"
                />
            </div>

            <!-- Chevron -->
            <ChevronRight
                class="text-muted-foreground h-4 w-4 shrink-0 transition-transform duration-200"
                :class="{ 'rotate-90': isOpen }"
            />
        </div>

        <!-- Expanded content -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div v-if="isOpen" class="px-4 pb-4 pl-[52px]">
                <!-- Preset chips -->
                <div class="mb-3 flex flex-wrap gap-1.5">
                    <Button
                        v-for="preset in presets"
                        :key="preset.value"
                        type="button"
                        size="sm"
                        :variant="
                            form.cron_expression === preset.value
                                ? 'default'
                                : 'outline'
                        "
                        class="h-7 rounded-md px-2.5 text-xs"
                        @click="selectPreset(preset.value)"
                    >
                        {{ preset.label }}
                    </Button>
                </div>

                <Separator class="mb-3" />

                <p class="text-muted-foreground mb-2 text-xs font-medium">
                    Custom cron expression
                </p>

                <!-- 5-segment cron inputs -->
                <div class="flex items-start gap-1.5">
                    <template
                        v-for="(segment, index) in cronSegments"
                        :key="segment"
                    >
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <Input
                                v-model="cronParts[index]"
                                type="text"
                                class="text-center font-mono text-sm font-medium"
                                @input="onSegmentInput"
                            />
                            <span class="text-muted-foreground text-[10px]">
                                {{ segment }}
                            </span>
                        </div>
                        <span
                            v-if="index < 4"
                            class="text-muted-foreground mt-2 text-base"
                            >·</span
                        >
                    </template>
                </div>

                <p
                    v-if="form.errors.cron_expression"
                    class="text-destructive mt-1.5 text-xs"
                >
                    {{ form.errors.cron_expression }}
                </p>

                <Separator class="my-3" />

                <!-- Bottom row: preview left, actions right -->
                <div class="mt-3 flex items-center justify-between gap-4">
                    <p class="text-muted-foreground text-xs">
                        <template v-if="previewLabel">
                            <span class="text-foreground font-medium">{{
                                previewLabel
                            }}</span>
                            <code
                                class="text-muted-foreground ml-1.5 font-mono text-[10px]"
                            >
                                {{ form.cron_expression }}
                            </code>
                        </template>
                        <template v-else-if="form.cron_expression">
                            <code class="font-mono text-xs">{{
                                form.cron_expression
                            }}</code>
                        </template>
                        <template v-else>
                            — pick a preset or enter a custom expression
                        </template>
                    </p>
                    <div class="flex shrink-0 items-center gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            @click="cancel"
                        >
                            Cancel
                        </Button>
                        <Button
                            size="sm"
                            :disabled="form.processing"
                            @click="save"
                        >
                            <Loader2
                                v-if="form.processing"
                                class="mr-1.5 h-3 w-3 animate-spin"
                            />
                            Save
                        </Button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
