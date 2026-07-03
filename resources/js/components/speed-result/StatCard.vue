<script setup lang="ts">
import type { Component } from "vue";
import { computed } from "vue";
import { Card, CardContent } from "@/components/ui/card";

type Tone = "accent" | "success" | "destructive" | "warning";

const props = defineProps<{
    label: string;
    value: number | string | null;
    unit: string;
    subLabel: string;
    icon: Component;
    tone: Tone;
    accentVar: string;
}>();

const TONE_STYLES: Record<
    Exclude<Tone, "accent">,
    { text: string; border: string; chip: string }
> = {
    success: {
        text: "text-emerald-600 dark:text-emerald-400",
        border: "border-l-emerald-600 dark:border-l-emerald-400",
        chip: "bg-emerald-600/10 dark:bg-emerald-400/10",
    },
    destructive: {
        text: "text-destructive",
        border: "border-l-destructive",
        chip: "bg-destructive/10",
    },
    warning: {
        text: "text-amber-600 dark:text-amber-400",
        border: "border-l-amber-600 dark:border-l-amber-400",
        chip: "bg-amber-600/10 dark:bg-amber-400/10",
    },
};

const isAccent = computed(() => props.tone === "accent");
const chipStyle = computed(() =>
    isAccent.value
        ? {
              background: `color-mix(in oklch, ${props.accentVar} 14%, transparent)`,
              color: props.accentVar,
          }
        : undefined,
);
</script>

<template>
    <Card
        class="border-l-[3px]"
        :class="
            !isAccent ? TONE_STYLES[tone as Exclude<Tone, 'accent'>].border : ''
        "
        :style="isAccent ? { borderLeftColor: accentVar } : undefined"
    >
        <CardContent class="p-4">
            <div class="flex items-start justify-between">
                <span class="text-sm font-medium">{{ label }}</span>
                <span
                    class="rounded-lg p-1.5"
                    :class="
                        !isAccent
                            ? TONE_STYLES[tone as Exclude<Tone, 'accent'>].chip
                            : ''
                    "
                    :style="chipStyle"
                >
                    <component
                        :is="icon"
                        class="size-3.5"
                        :class="
                            !isAccent
                                ? TONE_STYLES[tone as Exclude<Tone, 'accent'>]
                                      .text
                                : ''
                        "
                    />
                </span>
            </div>

            <p class="mt-2">
                <span
                    class="text-2xl font-bold tracking-tight"
                    :class="
                        !isAccent
                            ? TONE_STYLES[tone as Exclude<Tone, 'accent'>].text
                            : ''
                    "
                    :style="isAccent ? { color: accentVar } : undefined"
                >
                    {{ value ?? "—" }}
                </span>
                <span class="text-xs font-normal text-muted-foreground ml-1">{{
                    unit
                }}</span>
            </p>

            <p class="text-[11px] text-muted-foreground mt-1">{{ subLabel }}</p>
        </CardContent>
    </Card>
</template>
