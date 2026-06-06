<script setup lang="ts">
import { computed } from "vue";
import type { PublicSpeedResult } from "@/types/public";

const props = defineProps<{
    results: PublicSpeedResult[];
}>();

const fmt = (val: number | null, unit: string): string => {
    if (val === null) return "—";

    return `${val} ${unit}`;
};

const fmtDate = (iso: string): string => {
    return new Date(iso).toLocaleString(undefined, {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const rows = computed(() => props.results);
</script>

<template>
    <div class="bg-card border-border overflow-hidden rounded-lg border">
        <div
            class="bg-muted/50 grid grid-cols-[1fr_90px_90px_70px_70px_120px] border-b px-3 py-2"
        >
            <span
                class="text-muted-foreground text-[11px] font-medium uppercase tracking-wide"
                >Provider</span
            >
            <span
                class="text-muted-foreground text-[11px] font-medium uppercase tracking-wide"
                >Download</span
            >
            <span
                class="text-muted-foreground text-[11px] font-medium uppercase tracking-wide"
                >Upload</span
            >
            <span
                class="text-muted-foreground text-[11px] font-medium uppercase tracking-wide"
                >Ping</span
            >
            <span
                class="text-muted-foreground text-[11px] font-medium uppercase tracking-wide"
                >Jitter</span
            >
            <span
                class="text-muted-foreground text-[11px] font-medium uppercase tracking-wide"
                >Measured at</span
            >
        </div>
        <div
            v-for="row in rows"
            :key="row.id"
            class="border-border grid grid-cols-[1fr_90px_90px_70px_70px_120px] items-center border-b px-3 py-2 last:border-b-0"
        >
            <span class="text-sm">{{ row.provider_name }}</span>
            <span class="text-sm">{{ fmt(row.download_mbps, "Mbps") }}</span>
            <span class="text-sm">{{ fmt(row.upload_mbps, "Mbps") }}</span>
            <span class="text-sm">{{ fmt(row.ping_ms, "ms") }}</span>
            <span class="text-muted-foreground text-sm">{{
                fmt(row.jitter_ms, "ms")
            }}</span>
            <span class="text-muted-foreground text-sm">{{
                fmtDate(row.measured_at)
            }}</span>
        </div>
        <div
            v-if="rows.length === 0"
            class="text-muted-foreground px-3 py-6 text-center text-sm"
        >
            No results yet
        </div>
    </div>
</template>
