<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { ArrowLeft } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    Pagination,
    PaginationEllipsis,
    PaginationFirst,
    PaginationLast,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from "@/components/ui/pagination";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type { Webhook, WebhookDelivery } from "@/types/webhook";

const props = defineProps<{
    webhook: Webhook;
    deliveries: {
        data: WebhookDelivery[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            links: Array<{
                url: string | null;
                label: string;
                active: boolean;
            }>;
        };
    };
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Settings", href: "#" },
    {
        title: "Webhooks",
        href: route("speedtest.integration.webhooks.index", {}, false),
    },
    {
        title: props.webhook.name,
        href: route("speedtest.integration.webhooks.index", {}, false),
    },
    { title: "Delivery log", href: "#" },
];

const dotColor = (d: WebhookDelivery) => {
    if (d.success) return "bg-green-500";
    if (!d.status_code) return "bg-muted-foreground";
    return "bg-destructive";
};

const statusColor = (d: WebhookDelivery) => {
    if (d.success) return "text-green-700 dark:text-green-400";
    if (!d.status_code) return "text-muted-foreground";
    return "text-destructive";
};

const relativeTime = (iso: string) => {
    const diff = Date.now() - new Date(iso).getTime();
    const mins = Math.floor(diff / 60_000);
    if (mins < 1) return "just now";
    if (mins < 60) return `${mins} min ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs}h ago`;
    return `${Math.floor(hrs / 24)}d ago`;
};
</script>

<template>
    <Head :title="`Delivery log — ${webhook.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <!-- Page header -->
            <div class="flex items-center gap-3 py-5">
                <Button variant="ghost" size="icon" class="h-8 w-8" as-child>
                    <Link
                        :href="
                            route(
                                'speedtest.integration.webhooks.index',
                                {},
                                false,
                            )
                        "
                    >
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-xl font-semibold">Delivery log</h1>
                    <p class="text-muted-foreground mt-0.5 text-sm">
                        {{ webhook.name }} ·
                        <code class="font-mono text-xs">{{
                            webhook.method
                        }}</code>
                        ·
                        <span class="font-mono text-xs">{{
                            webhook.url_preview
                        }}</span>
                    </p>
                </div>
            </div>

            <!-- Stats row -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-muted rounded-lg p-4">
                    <p class="text-muted-foreground text-xs">
                        Total deliveries
                    </p>
                    <p class="mt-1 text-2xl font-medium">
                        {{ deliveries.meta.total ?? 0 }}
                    </p>
                </div>
                <div class="bg-muted rounded-lg p-4">
                    <p class="text-muted-foreground text-xs">Successful</p>
                    <p
                        class="mt-1 text-2xl font-medium text-green-700 dark:text-green-400"
                    >
                        {{ deliveries.data.filter((d) => d.success).length }}
                        <span class="text-muted-foreground text-sm font-normal"
                            >/ {{ deliveries.data.length }} this page</span
                        >
                    </p>
                </div>
                <div class="bg-muted rounded-lg p-4">
                    <p class="text-muted-foreground text-xs">Last fired</p>
                    <p class="mt-1 text-sm font-medium">
                        {{
                            webhook.last_fired_at
                                ? relativeTime(webhook.last_fired_at)
                                : "Never"
                        }}
                    </p>
                </div>
            </div>

            <!-- Full delivery table -->
            <Card class="overflow-hidden p-0">
                <CardHeader class="border-border border-b px-4 py-3">
                    <CardTitle class="text-sm font-medium"
                        >All deliveries</CardTitle
                    >
                    <CardDescription class="text-xs">
                        Showing page {{ deliveries.meta.current_page }} of
                        {{ deliveries.meta.last_page }} (
                        {{ deliveries.meta.total }} total items )
                    </CardDescription>
                </CardHeader>

                <!-- Column headers -->
                <div
                    class="bg-muted border-border grid border-b px-4 py-2"
                    style="grid-template-columns: 80px 80px 1fr 120px 80px 80px"
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

                <!-- Empty state -->
                <div
                    v-if="deliveries.data.length === 0"
                    class="text-muted-foreground py-12 text-center text-sm"
                >
                    No deliveries recorded yet.
                </div>

                <!-- Rows -->
                <div
                    v-for="delivery in deliveries.data"
                    :key="delivery.id"
                    class="border-border grid items-center border-b px-4 py-2.5 last:border-0"
                    style="grid-template-columns: 80px 80px 1fr 120px 80px 80px"
                >
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
                    <span
                        class="text-muted-foreground truncate font-mono text-[11px]"
                    >
                        {{ delivery.event }}
                    </span>
                    <span class="text-muted-foreground text-xs">
                        <template v-if="delivery.duration_ms"
                            >{{ delivery.duration_ms }}ms ·
                        </template>
                    </span>
                    <span class="text-muted-foreground text-xs">
                        {{ delivery.attempt }}/{{ delivery.max_attempts }}
                    </span>
                    <span class="text-muted-foreground text-xs">
                        {{ relativeTime(delivery.created_at) }}
                    </span>
                </div>
            </Card>

            <!-- Pagination -->
            <div
                v-if="deliveries.meta.last_page > 1"
                class="flex justify-center"
            >
                <Pagination
                    :total="deliveries.meta.total"
                    :items-per-page="deliveries.meta.per_page"
                    :page="deliveries.meta.current_page"
                    :sibling-count="1"
                    show-edges
                >
                    <PaginationContent v-slot="{ items }">
                        <PaginationFirst />
                        <PaginationPrevious />
                        <template v-for="(item, index) in items" :key="index">
                            <PaginationItem
                                v-if="item.type === 'page'"
                                :value="item.value"
                                as-child
                            >
                                <Button
                                    :variant="
                                        item.value ===
                                        deliveries.meta.current_page
                                            ? 'default'
                                            : 'outline'
                                    "
                                    class="h-8 w-8 p-0 text-xs"
                                    as-child
                                >
                                    <Link
                                        :href="
                                            route(
                                                'speedtest.integration.webhooks.deliveries',
                                                { webhook: webhook.id },
                                                false,
                                            ) +
                                            '?page=' +
                                            item.value
                                        "
                                    >
                                        {{ item.value }}
                                    </Link>
                                </Button>
                            </PaginationItem>
                            <PaginationEllipsis
                                v-else
                                :key="item.type"
                                :index="index"
                            />
                        </template>
                        <PaginationNext />
                        <PaginationLast />
                    </PaginationContent>
                </Pagination>
            </div>
        </div>
    </AppLayout>
</template>
