<script setup lang="ts">
import type { TDatePreset } from "@/composables/useDateRangePresets";

defineProps<{
    presets: TDatePreset[];
    activeKey: string | null;
}>();

defineEmits<{ select: [preset: TDatePreset] }>();
</script>

<template>
    <div class="flex flex-wrap gap-1.5">
        <button
            v-for="preset in presets"
            :key="preset.key"
            type="button"
            class="rounded-md border px-2 py-1 text-xs transition-colors"
            :class="
                activeKey === preset.key
                    ? 'border-transparent bg-muted font-medium'
                    : 'border-input hover:bg-muted'
            "
            @click="$emit('select', preset)"
        >
            {{ preset.label }}
        </button>
    </div>
</template>
