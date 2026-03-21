<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ExternalLink, Loader2, AlertTriangle } from "lucide-vue-next";
import { ref, computed } from "vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Switch } from "@/components/ui/switch";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type { Provider } from "@/types/provider";

const props = defineProps<{
    providers: Provider[];
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Settings", href: "#" },
    {
        title: "Speedtest Providers",
        href: route("speedtest.server.providers.index", {}, false),
    },
];

const activeTab = ref<string>(props.providers[0]?.slug ?? "speedtest");

// One form per provider keyed by slug
const forms = Object.fromEntries(
    props.providers.map((provider) => [
        provider.slug,
        useForm({
            is_enabled: provider.is_enabled,
            alert_on_failure: provider.alert_on_failure,
            server_url: provider.server_url ?? "",
            server_id: provider.server_id ?? "",
            extra_flags: provider.extra_flags ?? "",
        }),
    ]),
);

const activeProvider = computed<Provider>(
    () =>
        props.providers.find((p) => p.slug === activeTab.value) ??
        props.providers[0],
);

const activeForm = computed(() => forms[activeTab.value]);

function save(provider: Provider) {
    forms[provider.slug].patch(
        route("settings.providers.update", { provider: provider.slug }, false),
        { preserveScroll: true },
    );
}

function runNow(provider: Provider) {
    router.post(
        route("settings.providers.run-now", { provider: provider.slug }, false),
        {},
        { preserveScroll: true },
    );
}

function statusBadgeVariant(badge: Provider["status_badge"]) {
    return {
        success: "default",
        danger: "destructive",
        warning: "secondary",
        neutral: "outline",
    }[badge] as "default" | "destructive" | "secondary" | "outline";
}

function statusLabel(status: Provider["last_run_status"]) {
    return (
        {
            success: "Healthy",
            failed: "Failed",
            skipped: "Skipped",
            null: "Never run",
        }[status ?? "null"] ?? "Never run"
    );
}
</script>

<template>
    <Head title="Speedtest Providers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex flex-col gap-1 py-5">
                <h1 class="text-xl font-semibold">Speedtest Providers</h1>
                <p class="text-muted-foreground text-sm">
                    Configure and enable each speedtest provider
                </p>
            </div>

            <Tabs v-model="activeTab">
                <TabsList>
                    <TabsTrigger
                        v-for="provider in providers"
                        :key="provider.slug"
                        :value="provider.slug"
                    >
                        {{ provider.name }}
                        <span
                            v-if="provider.is_enabled"
                            class="bg-primary ml-1.5 inline-block h-1.5 w-1.5 rounded-full"
                        />
                    </TabsTrigger>
                </TabsList>

                <TabsContent
                    v-for="provider in providers"
                    :key="provider.slug"
                    :value="provider.slug"
                    class="mt-0 space-y-3"
                >
                    <!-- Chromium warning for Fast.com -->
                    <div
                        v-if="provider.requires_chromium"
                        class="bg-warning/10 border-warning/30 flex items-start gap-2 rounded-lg border p-3"
                    >
                        <AlertTriangle
                            class="text-warning mt-0.5 h-4 w-4 shrink-0"
                        />
                        <p class="text-warning text-sm">
                            Fast.com requires Chromium (Puppeteer) installed in
                            your Docker container. Ensure
                            <code class="font-mono text-xs"
                                >PUPPETEER_EXECUTABLE_PATH</code
                            >
                            is set correctly.
                        </p>
                    </div>

                    <!-- Status row -->
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <Badge
                                :variant="
                                    statusBadgeVariant(provider.status_badge)
                                "
                            >
                                {{ statusLabel(provider.last_run_status) }}
                            </Badge>
                            <span
                                v-if="provider.last_run_at"
                                class="text-muted-foreground text-xs"
                            >
                                Last run {{ provider.last_run_at }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="!provider.is_runnable"
                                @click="runNow(provider)"
                            >
                                Run now
                            </Button>

                            <Link
                                :href="provider.website_link"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-muted-foreground hover:text-foreground"
                            >
                                <ExternalLink class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>

                    <!-- Provider status -->
                    <Card>
                        <CardContent
                            class="flex items-center justify-between p-4"
                        >
                            <div class="space-y-0.5">
                                <p class="font-semibold">Provider status</p>
                                <p class="text-muted-foreground text-sm">
                                    Enable or disable this provider globally
                                </p>
                            </div>
                            <Switch
                                :checked="forms[provider.slug].is_enabled"
                                @update:checked="
                                    forms[provider.slug].is_enabled = $event
                                "
                            />
                        </CardContent>
                    </Card>

                    <!-- Server URL — LibreSpeed only -->
                    <Card v-if="provider.requires_server_url">
                        <CardContent
                            class="flex items-center justify-between gap-8 p-4"
                        >
                            <div class="space-y-0.5">
                                <p class="font-semibold">Server URL</p>
                                <p class="text-muted-foreground text-sm">
                                    Your self-hosted LibreSpeed instance URL
                                </p>
                            </div>
                            <Input
                                v-model="forms[provider.slug].server_url"
                                type="url"
                                placeholder="https://speed.example.com"
                                class="max-w-xs"
                            />
                        </CardContent>
                    </Card>

                    <!-- Server ID — Speedtest only -->
                    <Card v-if="provider.slug === 'speedtest'">
                        <CardContent
                            class="flex items-center justify-between gap-8 p-4"
                        >
                            <div class="space-y-0.5">
                                <p class="font-semibold">Server ID</p>
                                <p class="text-muted-foreground text-sm">
                                    Leave blank to auto-select nearest server
                                </p>
                            </div>
                            <Input
                                v-model="forms[provider.slug].server_id"
                                type="text"
                                placeholder="e.g. 12345"
                                class="max-w-xs"
                            />
                        </CardContent>
                    </Card>

                    <!-- Extra CLI flags -->
                    <Card>
                        <CardContent
                            class="flex items-center justify-between gap-8 p-4"
                        >
                            <div class="space-y-0.5">
                                <p class="font-semibold">Extra CLI flags</p>
                                <p class="text-muted-foreground text-sm">
                                    Appended verbatim to the speedtest command
                                </p>
                            </div>
                            <Input
                                v-model="forms[provider.slug].extra_flags"
                                type="text"
                                placeholder="--secure --format=json"
                                class="max-w-xs"
                            />
                        </CardContent>
                    </Card>

                    <!-- Failure alert -->
                    <Card>
                        <CardContent
                            class="flex items-center justify-between p-4"
                        >
                            <div class="space-y-0.5">
                                <p class="font-semibold">Failure alert</p>
                                <p class="text-muted-foreground text-sm">
                                    Notify when this provider run fails
                                </p>
                            </div>
                            <Switch
                                :checked="forms[provider.slug].alert_on_failure"
                                @update:checked="
                                    forms[provider.slug].alert_on_failure =
                                        $event
                                "
                            />
                        </CardContent>
                    </Card>

                    <!-- Save button -->
                    <div class="flex justify-end pt-2">
                        <Button
                            :disabled="forms[provider.slug].processing"
                            @click="save(provider)"
                        >
                            <Loader2
                                v-if="forms[provider.slug].processing"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            Save changes
                        </Button>
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
