<script setup lang="ts">
import { Clock } from "lucide-vue-next";
import { computed, ref } from "vue";
import { Badge } from "@/components/ui/badge";
import { Card } from "@/components/ui/card";
import type { ProviderCard } from "@/types/dashboard";

const props = defineProps<{
    provider: ProviderCard;
}>();

const faviconError = ref(false);

const statusVariant = computed(
    () =>
        ({
            success: "default",
            danger: "destructive",
            warning: "secondary",
            neutral: "outline",
        })[props.provider.status_badge] as
            | "default"
            | "destructive"
            | "secondary"
            | "outline",
);

const statusLabel = computed(
    () =>
        ({
            success: "healthy",
            danger: "failed",
            warning: "degraded",
            neutral: "unknown",
        })[props.provider.status_badge],
);

const lastRunLabel = computed(() => {
    if (!props.provider.last_run_at) {
        return "Never run";
    }

    const diff = Date.now() - new Date(props.provider.last_run_at).getTime();
    const mins = Math.floor(diff / 60_000);

    if (mins < 60) {
        return `${mins} min ago`;
    }

    const hrs = Math.floor(mins / 60);

    if (hrs < 24) {
        return `${hrs}h ago`;
    }

    return `${Math.floor(hrs / 24)}d ago`;
});
</script>

<template>
    <Card class="overflow-hidden p-0">
        <div class="p-4">
            <!-- Header -->
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div
                        class="border-border bg-muted flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-md border"
                    >
                        <img
                            v-if="!faviconError"
                            :src="`https://www.google.com/s2/favicons?domain=${provider.website_link}&sz=32`"
                            :alt="provider.name"
                            class="h-3.5 w-3.5 object-contain"
                            @error="faviconError = true"
                        />
                        <Clock
                            v-else
                            class="text-muted-foreground h-3.5 w-3.5"
                        />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-medium">{{
                            provider.name
                        }}</span>
                        <span class="text-muted-foreground text-[10px]">{{
                            lastRunLabel
                        }}</span>
                    </div>
                </div>
                <Badge :variant="statusVariant" class="text-[10px]">
                    {{ statusLabel }}
                </Badge>
            </div>

            <!-- Metrics -->
            <div class="mb-3 grid grid-cols-3 gap-1.5">
                <div class="bg-muted rounded-md p-2">
                    <div class="text-sm font-medium">
                        {{
                            provider.latest
                                ? provider.latest.download_mbps.toFixed(1)
                                : "—"
                        }}
                    </div>
                    <div class="text-muted-foreground text-[10px]">↓ Mbps</div>
                </div>
                <div class="bg-muted rounded-md p-2">
                    <div class="text-sm font-medium">
                        {{
                            provider.latest
                                ? provider.latest.upload_mbps.toFixed(1)
                                : "—"
                        }}
                    </div>
                    <div class="text-muted-foreground text-[10px]">↑ Mbps</div>
                </div>
                <div class="bg-muted rounded-md p-2">
                    <div class="text-sm font-medium">
                        {{
                            provider.latest
                                ? `${provider.latest.ping_ms.toFixed(0)}ms`
                                : "—"
                        }}
                    </div>
                    <div class="text-muted-foreground text-[10px]">ping</div>
                </div>
            </div>
        </div>
    </Card>
</template>
