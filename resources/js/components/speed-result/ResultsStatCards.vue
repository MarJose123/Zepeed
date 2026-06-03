<script setup lang="ts">
import { Card, CardContent } from "@/components/ui/card";
import type { TSpeedResultStats } from "@/types/speed-result";

const props = defineProps<{
    stats: TSpeedResultStats;
    metric: "download" | "upload" | "ping";
    accentVar: string;
}>();

const unit = props.metric === "ping" ? "ms" : "Mbps";
</script>

<template>
    <div class="grid grid-cols-4 gap-4">
        <Card class="border-l-[3px]" :style="`border-left-color:${accentVar}`">
            <CardContent class="p-4">
                <p
                    class="text-xs text-muted-foreground mb-1 flex items-center gap-1.5"
                >
                    <span
                        class="inline-block w-1.5 h-1.5 rounded-full shrink-0"
                        :style="`background:${accentVar}`"
                    />
                    Average
                </p>
                <p
                    class="text-2xl font-bold tracking-tight"
                    :style="`color:${accentVar}`"
                >
                    {{ stats.average ?? "—"
                    }}<span
                        class="text-xs font-normal text-muted-foreground ml-1"
                        >{{ unit }}</span
                    >
                </p>
                <p class="text-[11px] text-muted-foreground mt-1">
                    across {{ stats.total }} tests
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="p-4">
                <p
                    class="text-xs text-muted-foreground mb-1 flex items-center gap-1.5"
                >
                    <span
                        class="inline-block w-1.5 h-1.5 rounded-full shrink-0"
                        :style="`background:${accentVar}`"
                    />
                    {{ metric === "ping" ? "Best (lowest)" : "Peak" }}
                </p>
                <p
                    class="text-2xl font-bold tracking-tight"
                    :style="`color:${accentVar}`"
                >
                    {{ (metric === "ping" ? stats.best : stats.peak) ?? "—"
                    }}<span
                        class="text-xs font-normal text-muted-foreground ml-1"
                        >{{ unit }}</span
                    >
                </p>
                <p class="text-[11px] text-muted-foreground mt-1">&nbsp;</p>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="p-4">
                <p
                    class="text-xs text-muted-foreground mb-1 flex items-center gap-1.5"
                >
                    <span
                        class="inline-block w-1.5 h-1.5 rounded-full shrink-0 bg-destructive"
                    />
                    {{ metric === "ping" ? "Worst (highest)" : "Lowest" }}
                </p>
                <p class="text-2xl font-bold tracking-tight text-destructive">
                    {{ (metric === "ping" ? stats.worst : stats.lowest) ?? "—"
                    }}<span
                        class="text-xs font-normal text-muted-foreground ml-1"
                        >{{ unit }}</span
                    >
                </p>
                <p class="text-[11px] text-muted-foreground mt-1">&nbsp;</p>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="p-4">
                <p
                    class="text-xs text-muted-foreground mb-1 flex items-center gap-1.5"
                >
                    <span
                        class="inline-block w-1.5 h-1.5 rounded-full shrink-0 bg-amber-500"
                    />
                    {{ stats.threshold_label }}
                </p>
                <p class="text-2xl font-bold tracking-tight text-amber-500">
                    {{ stats.threshold_count
                    }}<span
                        class="text-xs font-normal text-muted-foreground ml-1"
                        >tests</span
                    >
                </p>
                <p class="text-[11px] text-muted-foreground mt-1">
                    {{ stats.threshold_pct }}% of total
                </p>
            </CardContent>
        </Card>
    </div>
</template>
