<script setup lang="ts">
interface LegendEntry {
    value: string;
    color?: string;
    dataKey?: unknown;
}

defineProps<{
    payload?: LegendEntry[];
    hiddenKeys: Set<string>;
}>();

const emit = defineEmits<{ toggle: [key: string] }>();
</script>

<template>
    <div
        class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 pt-2"
    >
        <button
            v-for="e in payload"
            :key="String(e.value)"
            type="button"
            class="flex items-center gap-1 transition-opacity"
            :class="{ 'opacity-35': hiddenKeys.has(String(e.dataKey)) }"
            @click="emit('toggle', String(e.dataKey))"
        >
            <span
                class="size-2 shrink-0 rounded-full"
                :style="{ backgroundColor: e.color }"
            />
            <span class="text-[10px] text-muted-foreground">{{ e.value }}</span>
        </button>
    </div>
</template>
