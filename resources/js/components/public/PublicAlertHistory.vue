<script setup lang="ts">
import type { PublicAlertItem } from '@/types/public'

defineProps<{
    alerts: PublicAlertItem[]
}>()

const fmtDate = (iso: string | null): string => {
    if (!iso) return '—'
    return new Date(iso).toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}
</script>

<template>
    <div class="bg-card border-border overflow-hidden rounded-lg border">
        <div
            v-for="alert in alerts"
            :key="alert.id"
            class="border-border flex items-center justify-between border-b px-3 py-2.5 last:border-b-0"
        >
            <p class="text-sm font-medium">{{ alert.name }}</p>
            <div class="flex shrink-0 items-center gap-2.5">
                <span
                    :class="alert.is_enabled
                        ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-400'
                        : 'bg-muted text-muted-foreground'"
                    class="rounded-full px-2 py-0.5 text-[10px]"
                >
                    {{ alert.is_enabled ? 'Active' : 'Disabled' }}
                </span>
                <span class="text-muted-foreground text-[11px]">{{ fmtDate(alert.updated_at) }}</span>
            </div>
        </div>
        <div v-if="alerts.length === 0" class="text-muted-foreground px-3 py-6 text-center text-sm">
            No alert rules configured
        </div>
    </div>
</template>
