<script setup lang="ts">
import { Check, Copy } from "@lucide/vue";
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import type { PrometheusFormData } from "@/types/prometheus";

const props = defineProps<{
    config: PrometheusFormData;
    scrapeUrl: string;
}>();

const emit = defineEmits<{
    "update:is_enabled": [value: boolean];
    "update:allowed_ips": [value: string[]];
}>();

const copied = ref(false);

function copyUrl() {
    navigator.clipboard.writeText(props.scrapeUrl);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
}

function onIpsInput(e: Event) {
    const raw = (e.target as HTMLTextAreaElement).value;
    const ips = raw
        .split("\n")
        .map((l) => l.trim())
        .filter(Boolean);
    emit("update:allowed_ips", ips);
}
</script>

<template>
    <Card>
        <CardHeader class="border-border border-b px-4 py-3">
            <CardTitle class="text-sm font-medium">Access control</CardTitle>
            <CardDescription class="text-[11px]">
                Enable the scrape endpoint and restrict access by IP or CIDR
                range.
            </CardDescription>
        </CardHeader>

        <CardContent class="flex flex-col gap-5 p-4">
            <!-- Enable toggle -->
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium">Enable Prometheus</p>
                    <p class="text-muted-foreground mt-0.5 text-[11px]">
                        Exposes the /metrics endpoint for scraping.
                    </p>
                </div>
                <Switch
                    :model-value="config.is_enabled"
                    @update:model-value="emit('update:is_enabled', $event)"
                />
            </div>

            <!-- Scrape URL -->
            <div class="flex flex-col gap-1.5">
                <Label class="text-xs font-medium">Scrape URL</Label>
                <div class="flex gap-2">
                    <Input
                        :model-value="scrapeUrl"
                        readonly
                        class="text-muted-foreground text-xs"
                    />
                    <Button variant="outline" size="sm" @click="copyUrl">
                        <Check
                            v-if="copied"
                            class="h-3.5 w-3.5 text-green-500"
                        />
                        <Copy v-else class="h-3.5 w-3.5" />
                    </Button>
                </div>
            </div>

            <!-- IP Allowlist -->
            <div class="flex flex-col gap-1.5">
                <Label class="text-xs font-medium">IP allowlist</Label>
                <Textarea
                    :model-value="config.allowed_ips.join('\n')"
                    placeholder="Leave empty to allow all IPs&#10;192.168.1.10&#10;10.0.0.0/8"
                    rows="4"
                    class="resize-none text-xs"
                    @input="onIpsInput"
                />
                <p class="text-muted-foreground text-[11px]">
                    One IP or CIDR per line. Empty means all IPs are allowed.
                    Supports ranges e.g. 10.0.0.0/8.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
