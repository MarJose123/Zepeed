<script setup lang="ts">
import { ArrowDown, ArrowUp, ChevronsUpDown } from "@lucide/vue";
import { computed } from "vue";
import type { TSpeedResultSortKey } from "@/types/speed-result";

const props = defineProps<{
    label: string;
    sortKey: TSpeedResultSortKey;
    currentSort: TSpeedResultSortKey | null;
    currentDirection: "asc" | "desc" | null;
    align?: "left" | "right";
}>();

const emit = defineEmits<{ toggle: [sortKey: TSpeedResultSortKey] }>();

const isActive = computed(() => props.currentSort === props.sortKey);
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center gap-1 text-sm font-medium transition-colors hover:text-foreground"
        :class="[
            isActive ? 'text-foreground' : 'text-muted-foreground',
            align === 'right' ? 'flex-row-reverse' : '',
        ]"
        @click="emit('toggle', sortKey)"
    >
        {{ label }}
        <ArrowUp
            v-if="isActive && currentDirection === 'asc'"
            class="size-3.5 text-primary"
        />
        <ArrowDown
            v-else-if="isActive && currentDirection === 'desc'"
            class="size-3.5 text-primary"
        />
        <ChevronsUpDown v-else class="size-3.5 opacity-50" />
    </button>
</template>
