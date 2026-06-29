<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { RefreshCw } from "@lucide/vue";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import type { PrometheusFormData } from "@/types/prometheus";

const PROVIDERS = [
    { slug: "ookla", label: "Ookla" },
    { slug: "librespeed", label: "LibreSpeed" },
    { slug: "netflix", label: "Netflix (Fast.com)" },
    { slug: "cloudflare", label: "Cloudflare" },
] as const;

const props = defineProps<{ config: PrometheusFormData }>();

const emit = defineEmits<{
    "update:cache_ttl": [value: number];
    "update:include_speed": [value: boolean];
    "update:include_ping": [value: boolean];
    "update:include_system": [value: boolean];
    "update:providers": [value: string[]];
}>();

function toggleProvider(slug: string, checked: boolean) {
    const next = checked
        ? [...props.config.providers, slug]
        : props.config.providers.filter((p) => p !== slug);
    emit("update:providers", next);
}

function flush() {
    router.post(
        route("speedtest.integration.prometheus.cache.flush"),
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Card>
        <CardHeader class="border-border border-b px-4 py-3">
            <CardTitle class="text-sm font-medium">
                Metric scope &amp; cache
            </CardTitle>
            <CardDescription class="text-[11px]">
                Choose which metric groups to expose and how long the output is
                cached.
            </CardDescription>
        </CardHeader>

        <CardContent class="flex flex-col gap-5 p-4">
            <!-- Cache TTL -->
            <div class="flex flex-col gap-1.5">
                <Label class="text-xs font-medium">Cache TTL (seconds)</Label>
                <div class="flex items-center gap-2">
                    <Input
                        type="number"
                        min="10"
                        max="3600"
                        :value="config.cache_ttl"
                        class="w-28 text-sm"
                        @input="
                            emit(
                                'update:cache_ttl',
                                Number(
                                    ($event.target as HTMLInputElement).value,
                                ),
                            )
                        "
                    />
                    <Button variant="outline" size="sm" @click="flush">
                        <RefreshCw class="mr-1.5 h-3.5 w-3.5" />
                        Flush now
                    </Button>
                </div>
                <p class="text-muted-foreground text-[11px]">
                    Rendered output is served from cache between scrapes. Min
                    10s, max 3600s.
                </p>
            </div>

            <!-- Speed metrics -->
            <div class="flex flex-col gap-3">
                <div class="flex items-start gap-3">
                    <Checkbox
                        id="include-speed"
                        :checked="config.include_speed"
                        @update:checked="emit('update:include_speed', $event)"
                    />
                    <div>
                        <Label
                            for="include-speed"
                            class="cursor-pointer text-sm font-medium"
                        >
                            Speed test metrics
                        </Label>
                        <p class="text-muted-foreground mt-0.5 text-[11px]">
                            Speed gauges, 24h health, provider state, failure
                            details.
                        </p>
                    </div>
                </div>

                <div
                    v-if="config.include_speed"
                    class="ml-6 flex flex-col gap-2"
                >
                    <p class="text-muted-foreground text-xs">
                        Providers to include:
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        <div
                            v-for="provider in PROVIDERS"
                            :key="provider.slug"
                            class="flex items-center gap-2"
                        >
                            <Checkbox
                                :id="`provider-${provider.slug}`"
                                :checked="
                                    config.providers.includes(provider.slug)
                                "
                                @update:checked="
                                    toggleProvider(provider.slug, $event)
                                "
                            />
                            <Label
                                :for="`provider-${provider.slug}`"
                                class="cursor-pointer text-xs"
                            >
                                {{ provider.label }}
                            </Label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ping metrics -->
            <div class="flex items-start gap-3">
                <Checkbox
                    id="include-ping"
                    :checked="config.include_ping"
                    @update:checked="emit('update:include_ping', $event)"
                />
                <div>
                    <Label
                        for="include-ping"
                        class="cursor-pointer text-sm font-medium"
                    >
                        Ping / network metrics
                    </Label>
                    <p class="text-muted-foreground mt-0.5 text-[11px]">
                        Ping target gauges and 24h probe health for all enabled
                        targets.
                    </p>
                </div>
            </div>

            <!-- System metrics -->
            <div class="flex items-start gap-3">
                <Checkbox
                    id="include-system"
                    :checked="config.include_system"
                    @update:checked="emit('update:include_system', $event)"
                />
                <div>
                    <Label
                        for="include-system"
                        class="cursor-pointer text-sm font-medium"
                    >
                        System metrics
                    </Label>
                    <p class="text-muted-foreground mt-0.5 text-[11px]">
                        Alert rules, maintenance mode, webhook deliveries, app
                        info.
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
