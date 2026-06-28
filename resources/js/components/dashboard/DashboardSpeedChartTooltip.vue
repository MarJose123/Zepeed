<script setup lang="ts">
interface TooltipEntry {
    readonly dataKey?: unknown;
    readonly name?: unknown;
    readonly value?: unknown;
    readonly color?: string;
}

defineProps<{
    active?: boolean;
    payload?: ReadonlyArray<TooltipEntry>;
    label?: string | number;
}>();
</script>

<template>
    <div
        v-if="active && payload?.length"
        class="bg-popover border-border text-popover-foreground rounded-lg border px-3 py-2 shadow-md"
    >
        <p class="mb-1.5 text-xs font-medium">{{ label }}</p>
        <div class="flex flex-col gap-1">
            <div
                v-for="e in payload"
                :key="String(e.dataKey)"
                class="flex items-center gap-2"
            >
                <span
                    class="size-2 shrink-0 rounded-full"
                    :style="{ backgroundColor: e.color }"
                />
                <span class="text-xs text-muted-foreground">{{ e.name }}:</span>
                <span class="text-xs font-medium">
                    {{
                        typeof e.value === "number"
                            ? `${e.value.toFixed(2)} Mbps`
                            : (e.value ?? "—")
                    }}
                </span>
            </div>
        </div>
    </div>
</template>
