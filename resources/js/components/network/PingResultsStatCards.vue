<script setup lang="ts">
import { Card, CardContent } from "@/components/ui/card";
import type { PingResultStats } from "@/types/ping";

defineProps<{ stats: PingResultStats }>();

const fmt = (v: number | null, digits = 1) =>
    v !== null ? Number(v).toFixed(digits) : "—";
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <Card>
            <CardContent class="p-4">
                <p class="text-xs text-muted-foreground mb-1">Tests Run</p>
                <p class="text-2xl font-bold tracking-tight">
                    {{ stats.total_tests }}
                </p>
                <p class="text-[11px] text-muted-foreground mt-1">
                    in selected window
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="p-4">
                <p class="text-xs text-muted-foreground mb-1">Avg Latency</p>
                <p class="text-2xl font-bold tracking-tight">
                    {{ fmt(stats.avg_latency_ms)
                    }}<span
                        class="text-xs font-normal text-muted-foreground ml-1"
                        >ms</span
                    >
                </p>
                <p class="text-[11px] text-muted-foreground mt-1">
                    average round-trip time
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="p-4">
                <p class="text-xs text-muted-foreground mb-1">
                    Avg Packet Loss
                </p>
                <p class="text-2xl font-bold tracking-tight">
                    {{ fmt(stats.avg_packet_loss)
                    }}<span
                        class="text-xs font-normal text-muted-foreground ml-1"
                        >%</span
                    >
                </p>
                <p class="text-[11px] text-muted-foreground mt-1">
                    across all targets
                </p>
            </CardContent>
        </Card>
    </div>
</template>
