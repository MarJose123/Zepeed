<script setup lang="ts">
import { Link, useHttp } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import type { Webhook, WebhookDelivery } from "@/types/webhook";

const props = defineProps<{
    webhook: Webhook;
    deliveries: WebhookDelivery[];
}>();

const localDeliveries = ref<WebhookDelivery[]>([...props.deliveries]);
const loading = ref(false);

// Reload delivery log when selected card changes
watch(
    () => props.webhook.id,
    async (id) => {
        loading.value = true;
        const http = useHttp<Record<string, never>, WebhookDelivery[]>();

        try {
            localDeliveries.value = await http.get(
                route(
                    "speedtest.integration.webhooks.deliveries.json",
                    { webhook: id },
                    false,
                ),
                { headers: { Accept: "application/json" } },
            );
        } finally {
            loading.value = false;
        }
    },
);

// Sync when parent refreshes (e.g. after a test)
watch(
    () => props.deliveries,
    (val) => {
        if (props.webhook.id === props.webhook.id) {
            localDeliveries.value = [...val];
        }
    },
);

const dotColor = (d: WebhookDelivery) => {
    if (d.success) {
        return "bg-green-500";
    }

    if (!d.status_code) {
        return "bg-muted-foreground";
    }

    return "bg-destructive";
};

const statusColor = (d: WebhookDelivery) => {
    if (d.success) {
        return "text-green-700 dark:text-green-400";
    }

    if (!d.status_code) {
        return "text-muted-foreground";
    }

    return "text-destructive";
};

const relativeTime = (iso: string) => {
    const diff = Date.now() - new Date(iso).getTime();
    const mins = Math.floor(diff / 60_000);

    if (mins < 1) {
        return "just now";
    }

    if (mins < 60) {
        return `${mins} min ago`;
    }

    const hrs = Math.floor(mins / 60);

    if (hrs < 24) {
        return `${hrs}h ago`;
    }

    return `${Math.floor(hrs / 24)}d ago`;
};
</script>

<template>
    <Card class="overflow-hidden p-0">
        <CardHeader class="border-border border-b px-4 py-3">
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="text-sm font-medium">
                        Delivery log — {{ webhook.name }}
                    </CardTitle>
                    <CardDescription class="text-xs">
                        Recent deliveries, retried with exponential backoff
                    </CardDescription>
                </div>
                <!-- View all — navigates to the full paginated delivery log page -->
                <Button variant="outline" size="sm" class="text-xs" as-child>
                    <Link
                        :href="
                            route(
                                'speedtest.integration.webhooks.deliveries',
                                { webhook: webhook.id },
                                false,
                            )
                        "
                    >
                        View all
                    </Link>
                </Button>
            </div>
        </CardHeader>

        <!-- Column header -->
        <div
            class="bg-muted border-border grid border-b px-4 py-2"
            style="grid-template-columns: 64px 80px 1fr 120px 80px 80px"
        >
            <span
                v-for="col in [
                    'Status',
                    'Response',
                    'Event',
                    'Duration',
                    'Retry',
                    'When',
                ]"
                :key="col"
                class="text-muted-foreground text-[10px] font-medium uppercase tracking-wide"
            >
                {{ col }}
            </span>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="space-y-0">
            <div
                v-for="n in 4"
                :key="n"
                class="border-border grid items-center border-b px-4 py-3 last:border-0"
                style="grid-template-columns: 80px 80px 1fr 120px 80px 80px"
            >
                <div class="bg-muted h-3 w-8 animate-pulse rounded" />
                <div class="bg-muted h-3 w-12 animate-pulse rounded" />
                <div class="bg-muted h-3 w-32 animate-pulse rounded" />
                <div class="bg-muted h-3 w-16 animate-pulse rounded" />
                <div class="bg-muted h-3 w-14 animate-pulse rounded" />
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-else-if="localDeliveries.length === 0"
            class="text-muted-foreground py-10 text-center text-sm"
        >
            No deliveries yet — test or trigger this webhook to see logs here.
        </div>

        <!-- Log rows -->
        <template v-else>
            <div
                v-for="delivery in localDeliveries"
                :key="delivery.id"
                class="border-border grid items-center border-b px-4 py-2.5 last:border-0"
                style="grid-template-columns: 80px 80px 1fr 120px 80px 80px"
            >
                <!-- Status -->
                <div class="flex items-center gap-1.5">
                    <div
                        class="h-2 w-2 shrink-0 rounded-full"
                        :class="dotColor(delivery)"
                    />
                    <span
                        class="text-xs font-medium"
                        :class="statusColor(delivery)"
                    >
                        {{ delivery.status_code ?? "—" }}
                    </span>
                </div>

                <!-- Status text -->
                <span
                    class="text-xs"
                    :class="
                        delivery.success
                            ? 'text-muted-foreground'
                            : 'text-destructive'
                    "
                >
                    {{ delivery.status_text ?? "—" }}
                </span>

                <!-- Event -->
                <span
                    class="text-muted-foreground truncate font-mono text-[11px]"
                >
                    {{ delivery.event }}
                </span>

                <!-- Duration  -->
                <span class="text-muted-foreground text-xs">
                    <template v-if="delivery.duration_ms"
                        >{{ delivery.duration_ms }}ms ·
                    </template>
                </span>

                <span class="text-muted-foreground text-xs">
                    {{ delivery.attempt }}/{{ delivery.max_attempts }}
                </span>

                <!-- Relative time -->
                <span class="text-muted-foreground text-xs">
                    {{ relativeTime(delivery.created_at) }}
                </span>
            </div>
        </template>
    </Card>
</template>
