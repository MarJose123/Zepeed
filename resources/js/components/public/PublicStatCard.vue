<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    unit?: string;
}>();
</script>

<template>
    <div class="bg-card border-border rounded-lg border p-3.5">
        <p class="text-muted-foreground mb-1.5 text-[11px]">{{ label }}</p>
        <p class="text-xl font-bold tracking-tight">
            {{ value
            }}<span
                v-if="unit"
                class="text-muted-foreground ml-1 text-xs font-normal"
                >{{ unit }}</span
            >
        </p>
    </div>
</template>
