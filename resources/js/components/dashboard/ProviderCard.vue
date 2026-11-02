<script setup lang="ts">
import { Clock } from "@lucide/vue";
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
            "default" | "destructive" | "secondary" | "outline",
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
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-md bg-muted"
                    >
                        <img
                            v-if="!faviconError"
                            :src="`https://www.google.com/s2/favicons?domain=${provider.website_link}&sz=32`"
                            :alt="provider.name"
                            class="size-5 object-cover"
                            @error="faviconError = true"
                        />
                        <Clock v-else class="text-muted-foreground size-3.5" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-medium leading-none">{{
                            provider.name
                        }}</span>
                        <span class="mt-1 text-xs text-muted-foreground">{{
                            lastRunLabel
                        }}</span>
                    </div>
                </div>
                <Badge :variant="statusVariant" class="text-xs px-1.5 py-0">
                    {{ statusLabel }}
                </Badge>
            </div>

            <!-- Metrics -->
            <div class="grid grid-cols-3 gap-2">
                <div class="bg-muted/50 rounded-md p-2.5">
                    <div class="text-sm font-semibold">
                        {{
                            provider.latest
                                ? provider.latest.download_mbps.toFixed(1)
                                : "—"
                        }}
                    </div>
                    <div class="mt-0.5 text-xs text-muted-foreground">
                        ↓ Mbps
                    </div>
                </div>
                <div class="bg-muted/50 rounded-md p-2.5">
                    <div class="text-sm font-semibold">
                        {{
                            provider.latest
                                ? provider.latest.upload_mbps.toFixed(1)
                                : "—"
                        }}
                    </div>
                    <div class="mt-0.5 text-xs text-muted-foreground">
                        ↑ Mbps
                    </div>
                </div>
                <div class="bg-muted/50 rounded-md p-2.5">
                    <div class="text-sm font-semibold">
                        {{
                            provider.latest
                                ? `${provider.latest.ping_ms.toFixed(0)}ms`
                                : "—"
                        }}
                    </div>
                    <div class="mt-0.5 text-xs text-muted-foreground">ping</div>
                </div>
            </div>
        </div>
    </Card>
</template>
