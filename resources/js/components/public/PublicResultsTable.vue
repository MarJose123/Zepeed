<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import type { PublicSpeedResult } from "@/types/public";

const props = defineProps<{
    results: PublicSpeedResult[];
}>();

const fmt = (val: number | null, unit: string): string =>
    val === null ? "—" : `${val} ${unit}`;

const fmtDate = (iso: string): string =>
    new Date(iso).toLocaleString(undefined, {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });

const rows = computed(() => props.results);
</script>

<template>
    <div class="border-border overflow-hidden rounded-lg border">
        <Table>
            <TableHeader>
                <TableRow class="bg-muted/50 hover:bg-muted/50">
                    <TableHead class="text-[11px]">Provider</TableHead>
                    <TableHead class="text-[11px]">Download</TableHead>
                    <TableHead class="text-[11px]">Upload</TableHead>
                    <TableHead class="text-[11px]">Ping</TableHead>
                    <TableHead class="text-[11px]">Jitter</TableHead>
                    <TableHead class="text-[11px]">Measured at</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="row in rows" :key="row.id">
                    <TableCell class="text-sm font-medium">{{
                        row.provider_name
                    }}</TableCell>
                    <TableCell class="text-sm">{{
                        fmt(row.download_mbps, "Mbps")
                    }}</TableCell>
                    <TableCell class="text-sm">{{
                        fmt(row.upload_mbps, "Mbps")
                    }}</TableCell>
                    <TableCell class="text-sm">{{
                        fmt(row.ping_ms, "ms")
                    }}</TableCell>
                    <TableCell class="text-muted-foreground text-sm">{{
                        fmt(row.jitter_ms, "ms")
                    }}</TableCell>
                    <TableCell class="text-muted-foreground text-sm">{{
                        fmtDate(row.measured_at)
                    }}</TableCell>
                </TableRow>
                <TableRow v-if="rows.length === 0">
                    <TableCell
                        colspan="6"
                        class="text-muted-foreground py-6 text-center text-sm"
                    >
                        No results yet
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
        <div class="border-border border-t px-3 py-2 text-center">
            <Link
                :href="route('login')"
                class="text-muted-foreground inline-flex items-center gap-1.5 text-[11px] hover:underline"
            >
                <svg
                    class="h-3 w-3"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                    />
                </svg>
                Sign in to view full history
            </Link>
        </div>
    </div>
</template>
