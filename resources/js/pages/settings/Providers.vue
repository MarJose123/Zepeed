<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import {
    Bell,
    ExternalLink,
    Info,
    Loader2,
    Radio,
    Server,
    Zap,
} from "@lucide/vue";
import { reactive, ref, watch } from "vue";
import ProviderDisableDialog from "@/components/speedtest/ProviderDisableDialog.vue";
import ProviderTestStatus from "@/components/speedtest/ProviderTestStatus.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Separator } from "@/components/ui/separator";
import { Switch } from "@/components/ui/switch";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { useNotification } from "@/composables/useNotification";
import {
    useProviderTestHttp,
    useSpeedtestTestChannel,
} from "@/composables/useSpeedtestTestChannel";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type {
    Provider,
    ProviderSchedulesMap,
    ProviderTestState,
} from "@/types/provider";

const props = defineProps<{
    providers: Provider[];
    schedulesMap: ProviderSchedulesMap;
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Settings", href: "#" },
    {
        title: "Speedtest Providers",
        href: route("speedtest.server.providers.index", {}, false),
    },
];

const { notify } = useNotification();
const { startTest, cancelTest: cancelProviderTest } = useProviderTestHttp();

const activeTab = ref<string>(props.providers[0]?.slug ?? "ookla");
const faviconError = ref(false);
const dialogOpen = ref(false);

// ── Per-provider test state ───────────────────────────────────────────────────

const testStates = reactive<Record<string, ProviderTestState>>(
    Object.fromEntries(
        props.providers.map((p) => [
            p.slug,
            {
                status: "idle",
                sessionId: null,
                result: null,
                errorMessage: null,
            },
        ]),
    ),
);

// Subscribe to the WebSocket test channel for every provider.
// useEcho (called inside useSpeedtestTestChannel) is lifecycle-scoped
// and cleans up automatically on component unmount.
props.providers.forEach((p) => {
    useSpeedtestTestChannel(p.slug, {
        onStarted: () => {
            testStates[p.slug].status = "running";
        },
        onCompleted: (payload) => {
            testStates[p.slug].status = "completed";
            testStates[p.slug].result = {
                download_mbps: payload.download_mbps,
                upload_mbps: payload.upload_mbps,
                ping_ms: payload.ping_ms,
                jitter_ms: payload.jitter_ms,
                server_name: payload.server_name,
                server_location: payload.server_location,
                isp: payload.isp,
            };
        },
        onException: (payload) => {
            testStates[p.slug].status = "failed";
            testStates[p.slug].errorMessage = payload.message;
        },
        onSkipped: () => {
            testStates[p.slug].status = "skipped";
        },
        onCancelled: () => {
            testStates[p.slug].status = "cancelled";
            testStates[p.slug].sessionId = null;
        },
    });
});

function onFaviconError() {
    faviconError.value = true;
}

// ── Run / test actions ────────────────────────────────────────────────────────

const runNow = (provider: Provider) => {
    router.post(
        route("speedtest.server.providers.run-now", {
            provider: provider.slug,
        }),
        {},
        { preserveScroll: true, onSuccess: () => router.reload() },
    );
};

const testRun = async (provider: Provider) => {
    const state = testStates[provider.slug];

    if (!state || state.status === "pending" || state.status === "running")
        return;

    state.status = "pending";
    state.result = null;
    state.errorMessage = null;
    state.sessionId = null;

    const data = await startTest(provider.slug);

    if (!data) {
        state.status = "idle";
        notify({
            type: "error",
            title: "Could not start test",
            message: "Server returned an unexpected response.",
        });

        return;
    }

    state.sessionId = data.test_session_id;
};

const cancelTest = async (provider: Provider) => {
    const state = testStates[provider.slug];

    if (!state?.sessionId) return;

    const ok = await cancelProviderTest(provider.slug, state.sessionId);

    if (!ok) {
        notify({
            type: "error",
            title: "Cancel failed",
            message: "Could not cancel the test.",
        });
    }
};

// ── Status helpers ────────────────────────────────────────────────────────────

const statusBadgeVariant = (badge: Provider["status_badge"]) =>
    ({
        success: "default",
        danger: "destructive",
        warning: "secondary",
        neutral: "outline",
    })[badge] as "default" | "destructive" | "secondary" | "outline";

const statusLabel = (status: Provider["last_run_status"]) =>
    ({
        success: "Healthy",
        failed: "Failed",
        skipped: "Skipped",
        null: "Never run",
    })[status ?? "null"] ?? "Never run";

// ── Form ─────────────────────────────────────────────────────────────────────

const form = useForm({
    is_enabled: false,
    server_url: "",
    server_id: "",
    alert_on_failure: false,
});

const syncFormToProvider = (slug: string) => {
    const provider = props.providers.find((p) => p.slug === slug);

    if (!provider) return;

    form.defaults({
        is_enabled: provider.is_enabled,
        server_url: provider.server_url ?? "",
        server_id: provider.server_id ?? "",
        alert_on_failure: provider.alert_on_failure,
    });
    form.reset();
};

watch(
    () => activeTab.value,
    (slug) => syncFormToProvider(slug),
    { immediate: true },
);
watch(
    () => props.providers,
    () => syncFormToProvider(activeTab.value),
    { immediate: true },
);

// ── Disable guard ─────────────────────────────────────────────────────────────

const onToggleEnabled = (newValue: boolean) => {
    const currentProvider = props.providers.find(
        (p) => p.slug === activeTab.value,
    );
    const wasEnabled = currentProvider?.is_enabled ?? false;

    if (wasEnabled && !newValue) {
        dialogOpen.value = true;

        return;
    }

    form.is_enabled = newValue;
};

const onDialogConfirm = () => {
    dialogOpen.value = false;
    form.is_enabled = false;
};

const onDialogCancel = () => {
    dialogOpen.value = false;
};

// ── Submit ────────────────────────────────────────────────────────────────────

const submitForm = () => {
    form.patch(
        route("speedtest.server.providers.update", {
            provider: activeTab.value,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload();
                form.reset();
            },
        },
    );
};
</script>

<template>
    <Head title="Speedtest Providers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex flex-col gap-1 py-5">
                <h1 class="text-xl font-bold tracking-tight">
                    Speedtest Providers
                </h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Configure and enable each speedtest provider
                </p>
            </div>

            <div
                v-if="!providers.length"
                class="rounded-lg border border-dashed border-border py-16 text-center"
            >
                <p class="text-sm text-muted-foreground">
                    No providers found. Run
                    <code class="text-xs"
                        >php artisan db:seed --class=ProviderSeeder</code
                    >
                </p>
            </div>

            <Tabs v-else v-model="activeTab">
                <TabsList>
                    <TabsTrigger
                        v-for="provider in providers"
                        :key="provider.slug"
                        :value="provider.slug"
                    >
                        {{ provider.name }}
                    </TabsTrigger>
                </TabsList>

                <TabsContent
                    v-for="provider in providers"
                    :key="provider.slug"
                    :value="provider.slug"
                    class="mt-4"
                >
                    <!-- Chromium warning -->
                    <div
                        v-if="provider.requires_chromium"
                        class="mb-4 flex items-start gap-2 rounded-lg border border-border bg-muted p-3"
                    >
                        <Info
                            class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                        />
                        <p class="text-sm text-muted-foreground">
                            This provider requires Chromium to run. Chromium is
                            pre-installed in the Docker image and no additional
                            setup is required.
                        </p>
                    </div>

                    <Card>
                        <CardHeader class="pb-1">
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <CardTitle class="text-sm font-medium pb-1">
                                        <div
                                            v-if="!faviconError"
                                            class="flex flex-row items-center gap-1"
                                        >
                                            <img
                                                :src="`https://www.google.com/s2/favicons?domain=${provider.website_link}&sz=32`"
                                                :alt="provider.name"
                                                class="size-7 object-cover"
                                                @error="onFaviconError"
                                            />
                                            <span>{{ provider.name }}</span>
                                        </div>
                                        <span v-else>{{ provider.name }}</span>
                                    </CardTitle>
                                    <CardDescription
                                        class="flex items-center gap-2"
                                    >
                                        <Badge
                                            :variant="
                                                statusBadgeVariant(
                                                    provider.status_badge,
                                                )
                                            "
                                        >
                                            {{
                                                statusLabel(
                                                    provider.last_run_status,
                                                )
                                            }}
                                        </Badge>
                                        <span
                                            v-if="provider.last_run_at"
                                            class="text-xs"
                                        >
                                            Last run: {{ provider.last_run_at }}
                                        </span>
                                        <span v-else class="text-xs"
                                            >Never run</span
                                        >
                                    </CardDescription>
                                </div>

                                <div class="flex items-center gap-3">
                                    <!-- Inline test status or Test button -->
                                    <ProviderTestStatus
                                        v-if="
                                            testStates[provider.slug]
                                                ?.status !== 'idle'
                                        "
                                        :state="testStates[provider.slug]"
                                        @cancel="cancelTest(provider)"
                                    />
                                    <Button
                                        v-else
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        :disabled="!provider.is_enabled"
                                        @click="testRun(provider)"
                                    >
                                        <Zap class="h-3.5 w-3.5" />
                                        Test
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        :disabled="!provider.is_runnable"
                                        @click="runNow(provider)"
                                    >
                                        Run now
                                    </Button>

                                    <a
                                        :href="provider.website_link"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-muted-foreground hover:text-foreground"
                                    >
                                        <ExternalLink class="h-4 w-4" />
                                    </a>
                                </div>
                            </div>
                        </CardHeader>

                        <Separator />

                        <CardContent>
                            <!-- Provider status -->
                            <Field class="py-4">
                                <div
                                    class="grid grid-cols-2 items-center gap-4"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-muted"
                                        >
                                            <Radio
                                                class="h-4 w-4 text-muted-foreground"
                                            />
                                        </div>
                                        <div>
                                            <FieldLabel
                                                >Provider status</FieldLabel
                                            >
                                            <FieldDescription>
                                                Enable or disable this provider
                                                globally
                                            </FieldDescription>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <Switch
                                            id="is_enabled"
                                            name="is_enabled"
                                            :model-value="form.is_enabled"
                                            @update:model-value="
                                                onToggleEnabled
                                            "
                                        />
                                    </div>
                                </div>
                            </Field>

                            <!-- Server URL — LibreSpeed only -->
                            <Field
                                v-if="provider.support_server_url"
                                class="py-4"
                            >
                                <div
                                    class="grid grid-cols-2 items-center gap-4"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-muted"
                                        >
                                            <Server
                                                class="h-4 w-4 text-muted-foreground"
                                            />
                                        </div>
                                        <div>
                                            <FieldLabel>Server URL</FieldLabel>
                                            <FieldDescription>
                                                Optional — leave blank to use a
                                                public LibreSpeed server or you
                                                can use your self-hosted
                                                LibreSpeed server.
                                            </FieldDescription>
                                            <FieldError
                                                v-if="form.errors.server_url"
                                            >
                                                {{ form.errors.server_url }}
                                            </FieldError>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <Input
                                            id="server_url"
                                            v-model="form.server_url"
                                            name="server_url"
                                            type="url"
                                            placeholder="https://speed.example.com"
                                            class="max-w-xs"
                                            :class="{
                                                'border-destructive':
                                                    form.errors.server_url,
                                            }"
                                        />
                                    </div>
                                </div>
                            </Field>

                            <!-- Server ID — Ookla only -->
                            <Field
                                v-if="provider.slug === 'ookla'"
                                class="py-4"
                            >
                                <div
                                    class="grid grid-cols-2 items-center gap-4"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-muted"
                                        >
                                            <Server
                                                class="h-4 w-4 text-muted-foreground"
                                            />
                                        </div>
                                        <div>
                                            <FieldLabel>Server ID</FieldLabel>
                                            <FieldDescription>
                                                Leave blank to auto-select
                                                nearest server
                                            </FieldDescription>
                                            <FieldError
                                                v-if="form.errors.server_id"
                                            >
                                                {{ form.errors.server_id }}
                                            </FieldError>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <Input
                                            id="server_id"
                                            v-model="form.server_id"
                                            name="server_id"
                                            type="text"
                                            placeholder="e.g. 12345"
                                            class="max-w-xs"
                                            :class="{
                                                'border-destructive':
                                                    form.errors.server_id,
                                            }"
                                        />
                                    </div>
                                </div>
                            </Field>

                            <!-- Failure alert -->
                            <Field class="py-4">
                                <div
                                    class="grid grid-cols-2 items-center gap-4"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-muted"
                                        >
                                            <Bell
                                                class="h-4 w-4 text-muted-foreground"
                                            />
                                        </div>
                                        <div>
                                            <FieldLabel
                                                >Failure alert</FieldLabel
                                            >
                                            <FieldDescription>
                                                Notify when this provider run
                                                fails
                                            </FieldDescription>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <Switch
                                            id="alert_on_failure"
                                            v-model="form.alert_on_failure"
                                            name="alert_on_failure"
                                        />
                                    </div>
                                </div>
                            </Field>
                        </CardContent>

                        <CardFooter>
                            <div class="ml-auto flex items-center">
                                <Button
                                    type="button"
                                    size="sm"
                                    :disabled="form.processing || !form.isDirty"
                                    @click.prevent="submitForm"
                                >
                                    <Loader2
                                        v-if="form.processing"
                                        class="mr-2 h-4 w-4 animate-spin"
                                    />
                                    Save changes
                                </Button>
                            </div>
                        </CardFooter>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>

        <ProviderDisableDialog
            :open="dialogOpen"
            :provider-name="
                providers.find((p) => p.slug === activeTab)?.name ?? ''
            "
            :schedules="
                schedulesMap[activeTab as keyof ProviderSchedulesMap] ?? []
            "
            @confirm="onDialogConfirm"
            @cancel="onDialogCancel"
        />
    </AppLayout>
</template>
