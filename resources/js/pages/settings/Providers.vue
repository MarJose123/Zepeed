<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import {
    ExternalLink,
    Loader2,
    Info,
    Radio,
    Server,
    Bell,
} from "lucide-vue-next";
import { ref, watch } from "vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
    CardFooter,
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
const testing = ref(false);

const runNow = (provider: Provider) => {
    router.post(
        route("speedtest.server.providers.run-now", {
            provider: provider.slug,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload();
            },
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

const statusBadgeVariant = (badge: Provider["status_badge"]) => {
    return {
        success: "default",
        danger: "destructive",
        warning: "secondary",
        neutral: "outline",
    }[badge] as "default" | "destructive" | "secondary" | "outline";
};

const statusLabel = (status: Provider["last_run_status"]) => {
    return (
        {
            success: "Healthy",
            failed: "Failed",
            skipped: "Skipped",
            null: "Never run",
        }[status ?? "null"] ?? "Never run"
    );
};

const form = useForm({
    is_enabled: false,
    server_url: "",
    server_id: "",
    alert_on_failure: false,
});

watch(
    () => activeTab.value,
    (newTab) => {
        const provider = props.providers.find((p) => p.slug === newTab);
        if (provider) {
            form.defaults({
                is_enabled: provider.is_enabled,
                server_url: provider.server_url ?? "",
                server_id: (provider.server_id as string) ?? "",
                alert_on_failure: provider.alert_on_failure,
            });
            form.reset();
        }
    },
    { immediate: true },
);

watch(
    () => props.providers,
    (updatedProviders) => {
        const provider = updatedProviders.find(
            (p) => p.slug === activeTab.value,
        );
        if (provider) {
            form.defaults({
                is_enabled: provider.is_enabled,
                server_url: provider.server_url ?? "",
                server_id: (provider.server_id as string) ?? "",
                alert_on_failure: provider.alert_on_failure,
            });
            form.reset();
        }
    },
    { immediate: true },
);

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
                <h1 class="text-xl font-semibold">Speedtest Providers</h1>
                <p class="text-muted-foreground text-sm">
                    Configure and enable each speedtest provider
                </p>
            </div>

            <div
                v-if="!providers.length"
                class="border-border rounded-lg border border-dashed py-16 text-center"
            >
                <p class="text-muted-foreground text-sm">
                    No providers found. Run
                    <code class="font-mono text-xs"
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
                        class="bg-muted border-border mb-4 flex items-start gap-2 rounded-lg border p-3"
                    >
                        <Info
                            class="text-muted-foreground mt-0.5 h-4 w-4 shrink-0"
                        />
                        <p class="text-muted-foreground text-sm">
                            This provider requires Chromium to run. Chromium is
                            pre-installed in the Docker image and no additional
                            setup is required.
                        </p>
                    </div>
                    <Card>
                        <CardHeader class="pb-1">
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <CardTitle class="text-base">
                                        {{ provider.name }}
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
                                    <!-- Test — always available regardless of is_enabled -->
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

                                    <!-- Run Now — respects is_runnable (enabled + not in maintenance) -->
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
                                            class="bg-muted flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
                                        >
                                            <Radio
                                                class="text-muted-foreground h-4 w-4"
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
                                            v-model="form.is_enabled"
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
                                            class="bg-muted flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
                                        >
                                            <Server
                                                class="text-muted-foreground h-4 w-4"
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
                                            name="server_url"
                                            v-model="form.server_url"
                                            type="url"
                                            id="server_url"
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

                            <!-- Server ID — Speedtest · Ookla only -->
                            <Field
                                v-if="provider.slug === 'speedtest'"
                                class="py-4"
                            >
                                <div
                                    class="grid grid-cols-2 items-center gap-4"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="bg-muted flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
                                        >
                                            <Server
                                                class="text-muted-foreground h-4 w-4"
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
                                            name="server_id"
                                            type="text"
                                            id="server_id"
                                            v-model="form.server_id"
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
                                            class="bg-muted flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
                                        >
                                            <Bell
                                                class="text-muted-foreground h-4 w-4"
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
                                            name="alert_on_failure"
                                            v-model="form.alert_on_failure"
                                        />
                                    </div>
                                </div>
                            </Field>
                        </CardContent>
                        <CardFooter>
                            <div class="flex items-center ml-auto">
                                <Button
                                    type="button"
                                    @click.prevent="submitForm"
                                    size="sm"
                                    :disabled="form.processing || !form.isDirty"
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
    </AppLayout>
</template>
