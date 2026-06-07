<script setup lang="ts">
import type { PublicAlertItem } from "@/types/public";

defineProps<{
    alerts: PublicAlertItem[];
}>();

const fmtDate = (iso: string | null): string => {
    if (!iso) return "—";

    return new Date(iso).toLocaleString(undefined, {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<template>
    <div class="border-border overflow-hidden rounded-lg border">
        <div
            v-for="alert in alerts"
            :key="alert.id"
            class="border-border flex flex-col gap-1.5 border-b px-4 py-3 last:border-b-0"
        >
            <p class="text-sm font-medium">{{ alert.name }}</p>
            <div class="flex items-center gap-2">
                <span
                    :class="
                        alert.is_enabled
                            ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-400'
                            : 'bg-muted text-muted-foreground'
                    "
                    class="rounded-full px-2.5 py-0.5 text-[10px] font-medium"
                >
                    {{ alert.is_enabled ? "Active" : "Disabled" }}
                </span>
                <span class="text-muted-foreground text-[11px]">{{
                    fmtDate(alert.updated_at)
                }}</span>
            </div>
        </div>
        <div
            v-if="alerts.length === 0"
            class="text-muted-foreground px-4 py-6 text-center text-sm"
        >
            No alert rules configured
        </div>
    </div>
</template>
