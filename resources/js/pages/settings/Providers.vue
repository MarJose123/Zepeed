<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import {
    Bell,
    ExternalLink,
    Info,
    Loader2,
    Radio,
    Server,
} from "lucide-vue-next";
import { ref, watch } from "vue";
import ProviderDisableDialog from "@/components/speedtest/ProviderDisableDialog.vue";
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
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type { Provider, ProviderSchedulesMap } from "@/types/provider";

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

const activeTab = ref<string>(props.providers[0]?.slug ?? "ookla");
const testing = ref(false);
const faviconError = ref(false);
const dialogOpen = ref(false);

function onFaviconError() {
    faviconError.value = true;
}

const runNow = (provider: Provider) => {
    router.post(
        route("speedtest.server.providers.run-now", {
            provider: provider.slug,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => router.reload(),
        },
    );
};

const testRun = (provider: Provider) => {
    testing.value = true;
    router.post(
        route(
            "speedtest.server.providers.test",
            { provider: provider.slug },
            false,
        ),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                testing.value = false;
            },
        },
    );
};

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

// ── Form ────────────────────────────────────────────────────────────────────
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
        server_id: (provider.server_id as string) ?? "",
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

// ── Disable guard ────────────────────────────────────────────────────────────
/**
 * Called by the Switch @update:modelValue.
 * Only intercepts the transition enabled → disabled.
 * Enabling a disabled provider proceeds without a dialog.
 */
const onToggleEnabled = (newValue: boolean) => {
    const currentProvider = props.providers.find(
        (p) => p.slug === activeTab.value,
    );
    const wasEnabled = currentProvider?.is_enabled ?? false;

    if (wasEnabled && !newValue) {
        // Disabling an active provider — show confirmation first,
        // do NOT update the form yet.
        dialogOpen.value = true;

        return;
    }

    // Enabling — apply directly.
    form.is_enabled = newValue;
};

const onDialogConfirm = () => {
    dialogOpen.value = false;
    form.is_enabled = false;
};

const onDialogCancel = () => {
    dialogOpen.value = false;
    // Revert: the form still holds the original `true` value, nothing to do.
};

// ── Submit ───────────────────────────────────────────────────────────────────
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
                                <div class="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        :disabled="testing"
                                        @click="testRun(provider)"
                                    >
                                        <Loader2
                                            v-if="testing"
                                            class="mr-2 h-4 w-4 animate-spin"
                                        />
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

        <!-- Disable confirmation dialog — rendered outside Tabs to avoid stacking context issues -->
        <ProviderDisableDialog
            :open="dialogOpen"
            :provider-name="
                providers.find((p) => p.slug === activeTab)?.name ?? ''
            "
            :schedules="schedulesMap[activeTab] ?? []"
            @confirm="onDialogConfirm"
            @cancel="onDialogCancel"
        />
    </AppLayout>
</template>
