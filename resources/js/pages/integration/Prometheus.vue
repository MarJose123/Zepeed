<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import PrometheusConfigCard from "@/components/prometheus/PrometheusConfigCard.vue";
import PrometheusMetricsCard from "@/components/prometheus/PrometheusMetricsCard.vue";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type { PrometheusConfig } from "@/types/prometheus";

const props = defineProps<{ config: PrometheusConfig }>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Integration", href: "#" },
    {
        title: "Prometheus",
        href: route("speedtest.integration.prometheus.index", {}, false),
    },
];

const scrapeUrl = `${window.location.origin}/metrics`;

const form = useForm({
    is_enabled: props.config.is_enabled,
    allowed_ips: props.config.allowed_ips ?? [],
    cache_ttl: props.config.cache_ttl,
    include_speed: props.config.include_speed,
    include_ping: props.config.include_ping,
    include_system: props.config.include_system,
    providers: props.config.providers ?? [],
});

function save() {
    form.post(route("speedtest.integration.prometheus.update", {}, false), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Prometheus" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex items-start justify-between gap-3 py-5">
                <div>
                    <h1 class="text-xl font-bold tracking-tight">Prometheus</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Expose a /metrics scrape endpoint for Prometheus and
                        Grafana.
                    </p>
                </div>
                <Button :disabled="form.processing" @click="save">
                    {{ form.processing ? "Saving…" : "Save changes" }}
                </Button>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <PrometheusConfigCard
                    :config="form"
                    :scrape-url="scrapeUrl"
                    @update:is_enabled="form.is_enabled = $event"
                    @update:allowed_ips="form.allowed_ips = $event"
                />
                <PrometheusMetricsCard
                    :config="form"
                    @update:cache_ttl="form.cache_ttl = $event"
                    @update:include_speed="form.include_speed = $event"
                    @update:include_ping="form.include_ping = $event"
                    @update:include_system="form.include_system = $event"
                    @update:providers="form.providers = $event"
                />
            </div>
        </div>
    </AppLayout>
</template>
