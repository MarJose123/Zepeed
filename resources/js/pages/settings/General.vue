<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import {
    Activity,
    AlertTriangle,
    Check,
    Clock,
    Database,
    Eye,
    EyeOff,
    Globe,
    Info,
    Key,
    RefreshCw,
    RotateCcw,
    Save,
    Server,
    Shield,
    Trash2,
    Zap,
} from "lucide-vue-next";
import { computed, ref } from "vue";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";
import { Switch } from "@/components/ui/switch";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type {
    TCacheKey,
    TDangerAction,
    TDangerActionConfig,
    TGeneralSettingsPageProps,
    TJobStatus,
} from "@/types/general-setting";

// ─── Breadcrumbs ──────────────────────────────────────────────────────────

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Settings", href: "#" },
    {
        title: "General",
        href: route("speedtest.general-settings.edit", {}, false),
    },
];

// ─── Props ────────────────────────────────────────────────────────────────

const props = defineProps<TGeneralSettingsPageProps>();

// ─── Form ─────────────────────────────────────────────────────────────────

const form = useForm({
    app_url: props.settings.app_url,
    timezone: props.settings.timezone,
    maintenance_enabled: props.settings.maintenance_enabled,
    bypass_secret: props.settings.bypass_secret,
    retry_after_value: props.settings.retry_after_value,
    retry_after_unit: props.settings.retry_after_unit,
    maintenance_redirect: props.settings.maintenance_redirect,
    result_auto_purge: props.settings.result_auto_purge,
    result_retention_days: props.settings.result_retention_days,
    prune_schedule: props.settings.prune_schedule,
    prune_cron: props.settings.prune_cron,
    batch_size: props.settings.batch_size,
    exempt_failed: props.settings.exempt_failed,
    webhook_retention_days: props.settings.webhook_retention_days,
    webhook_extended_retention: props.settings.webhook_extended_retention,
});

const submit = () => {
    form.patch(route("speedtest.general-settings.update"), {
        preserveScroll: true,
    });
};

const discard = () => form.reset();

// ─── Cache management ─────────────────────────────────────────────────────

const cacheClearing = ref<Record<TCacheKey, boolean>>({
    app: false,
    config: false,
    route: false,
    view: false,
});
const cacheCleared = ref<Record<TCacheKey, boolean>>({
    app: false,
    config: false,
    route: false,
    view: false,
});

const clearCache = (key: TCacheKey) => {
    cacheClearing.value[key] = true;

    router.post(
        route("speedtest.general-settings.cache.clear", { type: key }),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                cacheClearing.value[key] = false;
            },
            onSuccess: () => {
                cacheCleared.value[key] = true;
                setTimeout(() => {
                    cacheCleared.value[key] = false;
                }, 2500);
            },
        },
    );
};

const clearAllCache = () => {
    const keys: TCacheKey[] = ["app", "config", "route", "view"];
    keys.forEach((k, i) => setTimeout(() => clearCache(k), i * 160));
};

// ─── Danger zone ──────────────────────────────────────────────────────────

const dangerOpen = ref<Record<TDangerAction, boolean>>({
    clear_results: false,
    clear_log: false,
    reset_config: false,
    factory_reset: false,
});
const dangerConfirm = ref<Record<TDangerAction, string>>({
    clear_results: "",
    clear_log: "",
    reset_config: "",
    factory_reset: "",
});
const dangerProcessing = ref<Record<TDangerAction, boolean>>({
    clear_results: false,
    clear_log: false,
    reset_config: false,
    factory_reset: false,
});

const toggleDanger = (key: TDangerAction) => {
    dangerOpen.value[key] = !dangerOpen.value[key];

    if (!dangerOpen.value[key]) dangerConfirm.value[key] = "";
};

const executeDanger = (action: TDangerAction) => {
    dangerProcessing.value[action] = true;

    router.post(
        route("speedtest.general-settings.danger", { action }),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                dangerProcessing.value[action] = false;
            },
            onSuccess: () => {
                dangerOpen.value[action] = false;
                dangerConfirm.value[action] = "";
            },
        },
    );
};

// ─── Bypass secret ────────────────────────────────────────────────────────

const showSecret = ref(false);

const generateSecret = () => {
    const chars =
        "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    form.bypass_secret = Array.from(
        { length: 16 },
        () => chars[Math.floor(Math.random() * chars.length)],
    ).join("");
};

// ─── Computed ─────────────────────────────────────────────────────────────

const retryAfterSeconds = computed<number>(() =>
    form.retry_after_unit === "minutes"
        ? form.retry_after_value * 60
        : form.retry_after_value,
);

// ─── Static config ────────────────────────────────────────────────────────

const cacheTypes: { key: TCacheKey; label: string; desc: string }[] = [
    {
        key: "app",
        label: "Application cache",
        desc: "Clears runtime data cached via Cache::put()",
    },
    {
        key: "config",
        label: "Config cache",
        desc: "Re-reads all config files from disk",
    },
    {
        key: "route",
        label: "Route cache",
        desc: "Forces route re-registration on next request",
    },
    {
        key: "view",
        label: "View cache",
        desc: "Recompiles all Blade templates",
    },
];

const dangerActions: TDangerActionConfig[] = [
    {
        key: "clear_results",
        title: "Clear all speed results",
        desc: "Truncates the speed_results table. All historical test rows are permanently deleted. Providers, alert rules, schedules, and mail providers are not affected.",
        word: "CLEAR RESULTS",
        label: "Clear results",
        detail: "All speedtest rows will be permanently deleted. This cannot be undone.",
    },
    {
        key: "clear_log",
        title: "Clear webhook delivery log",
        desc: "Deletes all records from webhook_deliveries. Webhook endpoints and configurations are preserved.",
        word: "CLEAR LOG",
        label: "Clear log",
        detail: "All webhook delivery records will be deleted. Endpoint configurations are preserved.",
    },
    {
        key: "reset_config",
        title: "Reset all configuration",
        desc: "Deletes all providers, alert rules, mail providers, webhooks, email templates, schedules, and maintenance windows. Speed results and user accounts are fully preserved.",
        word: "RESET CONFIG",
        label: "Reset config",
        detail: "All configuration will be wiped. Speed results and user accounts are fully preserved.",
    },
    {
        key: "factory_reset",
        title: "⚠ Factory reset",
        desc: "Truncates all tables except users. Re-runs all database seeders. Returns Zepeed to a completely fresh state.",
        word: "FACTORY RESET",
        label: "Factory reset",
        detail: "All data except users will be permanently erased and seeders will re-run.",
    },
];

const retentionOptions = [
    { value: 30, label: "30 days" },
    { value: 60, label: "60 days" },
    { value: 90, label: "90 days" },
    { value: 180, label: "180 days" },
    { value: 365, label: "1 year" },
    { value: 730, label: "2 years" },
];

const scheduleOptions = [
    { value: "daily_02", label: "Daily at 02:00" },
    { value: "daily_04", label: "Daily at 04:00" },
    { value: "weekly", label: "Weekly (Sun 03:00)" },
    { value: "custom", label: "Custom cron" },
];

// ─── Helpers ──────────────────────────────────────────────────────────────

const jobStatusVariant = (
    status: TJobStatus,
): "default" | "secondary" | "outline" =>
    status === "healthy"
        ? "default"
        : status === "pending"
          ? "secondary"
          : "outline";

const retentionPct = (current: number, max: number): number =>
    Math.min(100, Math.round((current / max) * 100));

const barColor = (pct: number): string =>
    pct > 80 ? "bg-destructive" : pct > 55 ? "bg-amber-500" : "bg-foreground";
</script>

<template>
    <Head title="General Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-5 flex flex-1 flex-col gap-4 p-4 pt-0">
            <!-- ── Page header ──────────────────────────────────────── -->
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-bold tracking-tight">
                        General Settings
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Manage application preferences, maintenance, and data
                        lifecycle.
                    </p>
                </div>
            </div>

            <!-- ── Tabs ─────────────────────────────────────────────── -->
            <Tabs default-value="overview">
                <TabsList>
                    <TabsTrigger value="overview">
                        <Activity class="size-3.5 mr-1.5" />
                        Overview
                    </TabsTrigger>
                    <TabsTrigger value="maintenance">
                        <Zap class="size-3.5 mr-1.5" />
                        Maintenance
                        <Badge
                            v-if="form.maintenance_enabled"
                            variant="outline"
                            class="ml-1.5 text-amber-600 border-amber-400 bg-amber-50 text-[10px] px-1.5 py-0"
                        >
                            ON
                        </Badge>
                    </TabsTrigger>
                    <TabsTrigger value="retention">
                        <Database class="size-3.5 mr-1.5" />
                        Data Retention
                    </TabsTrigger>
                    <TabsTrigger value="danger">
                        <AlertTriangle class="size-3.5 mr-1.5" />
                        Danger Zone
                    </TabsTrigger>
                </TabsList>

                <!-- ════════════════════════════════════════════════════
                     OVERVIEW
                ════════════════════════════════════════════════════ -->
                <TabsContent value="overview" class="mt-5 space-y-4">
                    <!-- Stat cards -->
                    <div class="grid grid-cols-4 gap-3">
                        <Card>
                            <CardContent class="p-4 flex items-start gap-3">
                                <div
                                    class="size-9 rounded-md bg-muted flex items-center justify-center shrink-0"
                                >
                                    <Activity
                                        class="size-4 text-muted-foreground"
                                    />
                                </div>
                                <div>
                                    <p
                                        class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground"
                                    >
                                        Total Results
                                    </p>
                                    <p
                                        class="text-lg font-bold mt-0.5 leading-none"
                                    >
                                        {{
                                            stats.total_results.toLocaleString()
                                        }}
                                    </p>
                                    <p
                                        class="text-[11px] text-muted-foreground mt-1"
                                    >
                                        ↑ {{ stats.results_this_week }} this
                                        week
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-4 flex items-start gap-3">
                                <div
                                    class="size-9 rounded-md bg-blue-50 flex items-center justify-center shrink-0"
                                >
                                    <Database class="size-4 text-blue-600" />
                                </div>
                                <div>
                                    <p
                                        class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground"
                                    >
                                        Database Size
                                    </p>
                                    <p
                                        class="text-lg font-bold mt-0.5 leading-none"
                                    >
                                        {{ stats.db_size_mb }} MB
                                    </p>
                                    <p
                                        class="text-[11px] text-muted-foreground mt-1"
                                    >
                                        {{ stats.db_name }} database
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-4 flex items-start gap-3">
                                <div
                                    class="size-9 rounded-md bg-emerald-50 flex items-center justify-center shrink-0"
                                >
                                    <Shield class="size-4 text-emerald-600" />
                                </div>
                                <div>
                                    <p
                                        class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground"
                                    >
                                        Uptime
                                    </p>
                                    <p
                                        class="text-lg font-bold mt-0.5 leading-none"
                                    >
                                        {{ stats.uptime_percent }}%
                                    </p>
                                    <p
                                        class="text-[11px] text-muted-foreground mt-1"
                                    >
                                        last 30 days
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Two-column layout -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Application -->
                        <Card>
                            <CardHeader class="pb-3">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="size-8 rounded-md bg-muted flex items-center justify-center shrink-0"
                                    >
                                        <Globe
                                            class="size-3.5 text-muted-foreground"
                                        />
                                    </div>
                                    <div>
                                        <CardTitle class="text-sm"
                                            >Application</CardTitle
                                        >
                                        <CardDescription
                                            >Identity &amp; display
                                            preferences</CardDescription
                                        >
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <Field class="space-y-0">
                                    <FieldContent>
                                        <FieldLabel for="app_url"
                                            >App URL</FieldLabel
                                        >
                                        <FieldDescription
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            Used in notification links and share
                                            URLs
                                        </FieldDescription>
                                        <Input
                                            id="app_url"
                                            v-model="form.app_url"
                                            placeholder="https://zepeed.local"
                                            :class="{
                                                'border-destructive':
                                                    form.errors.app_url,
                                            }"
                                        />
                                        <FieldError
                                            v-if="form.errors.app_url"
                                            class="text-xs"
                                        >
                                            {{ form.errors.app_url }}
                                        </FieldError>
                                    </FieldContent>
                                </Field>
                                <Field class="space-y-1.5">
                                    <FieldContent>
                                        <FieldLabel for="timezone"
                                            >Timezone</FieldLabel
                                        >
                                        <FieldDescription
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            All timestamps and schedules
                                        </FieldDescription>
                                        <Select v-model="form.timezone">
                                            <SelectTrigger id="timezone">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent class="max-h-60">
                                                <SelectItem
                                                    v-for="tz in timezones"
                                                    :key="tz"
                                                    :value="tz"
                                                >
                                                    {{ tz }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <FieldError
                                            v-if="form.errors.timezone"
                                            class="text-xs text-destructive"
                                        >
                                            {{ form.errors.timezone }}
                                        </FieldError>
                                    </FieldContent>
                                </Field>
                            </CardContent>
                        </Card>

                        <!-- Right column -->
                        <div class="space-y-4">
                            <!-- Scheduler -->
                            <Card>
                                <CardHeader class="pb-3">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="size-8 rounded-md bg-muted flex items-center justify-center shrink-0"
                                        >
                                            <Server
                                                class="size-3.5 text-muted-foreground"
                                            />
                                        </div>
                                        <div>
                                            <CardTitle class="text-sm"
                                                >Scheduler</CardTitle
                                            >
                                            <CardDescription
                                                >Background job
                                                status</CardDescription
                                            >
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent
                                    class="divide-y divide-border/60 p-0 px-6 pb-4"
                                >
                                    <div
                                        v-for="job in scheduler_jobs"
                                        :key="job.name"
                                        class="grid grid-cols-[1fr_auto_auto] gap-3 items-center py-2.5"
                                    >
                                        <div>
                                            <p class="text-[13px] font-medium">
                                                {{ job.name }}
                                            </p>
                                            <p
                                                class="text-[11px] text-muted-foreground mt-0.5"
                                            >
                                                {{ job.description }}
                                            </p>
                                        </div>
                                        <span
                                            class="text-[11px] text-muted-foreground whitespace-nowrap"
                                        >
                                            {{ job.last_run }}
                                        </span>
                                        <Badge
                                            :variant="
                                                jobStatusVariant(job.status)
                                            "
                                            class="text-[10px]"
                                        >
                                            {{ job.status }}
                                        </Badge>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Storage breakdown -->
                            <Card>
                                <CardHeader class="pb-3">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="size-8 rounded-md bg-blue-50 flex items-center justify-center shrink-0"
                                        >
                                            <Database
                                                class="size-3.5 text-blue-600"
                                            />
                                        </div>
                                        <div>
                                            <CardTitle class="text-sm"
                                                >Storage breakdown</CardTitle
                                            >
                                            <CardDescription>
                                                Database table sizes ·
                                                {{ stats.db_size_mb }} MB total
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent class="space-y-3">
                                    <div
                                        v-for="tbl in storage_tables"
                                        :key="tbl.name"
                                    >
                                        <div
                                            class="flex justify-between mb-1.5"
                                        >
                                            <span
                                                class="text-[12px] font-medium"
                                                >{{ tbl.name }}</span
                                            >
                                            <span
                                                class="text-[11px] text-muted-foreground"
                                            >
                                                {{ tbl.size_display }} |
                                                {{
                                                    tbl.row_count.toLocaleString()
                                                }}
                                                {{
                                                    tbl.row_count === 1
                                                        ? "row"
                                                        : "rows"
                                                }}
                                            </span>
                                        </div>
                                        <div
                                            class="h-1.5 rounded-full bg-muted overflow-hidden"
                                        >
                                            <div
                                                class="h-full rounded-full transition-all duration-500"
                                                :class="
                                                    barColor(tbl.percentage)
                                                "
                                                :style="{
                                                    width: `${tbl.percentage}%`,
                                                }"
                                            />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-2 pb-2">
                        <Button
                            variant="outline"
                            :disabled="!form.isDirty || form.processing"
                            @click="discard"
                        >
                            <RotateCcw class="size-3.5 mr-1.5" />Discard
                        </Button>
                        <Button
                            :disabled="!form.isDirty || form.processing"
                            @click="submit"
                        >
                            <Save class="size-3.5 mr-1.5" />
                            {{ form.processing ? "Saving…" : "Save changes" }}
                        </Button>
                    </div>
                </TabsContent>

                <!-- ════════════════════════════════════════════════════
                     MAINTENANCE
                ════════════════════════════════════════════════════ -->
                <TabsContent value="maintenance" class="mt-5 space-y-4">
                    <Alert>
                        <Info class="size-4" />
                        <AlertDescription as-child>
                            <p class="text-muted-foreground text-sm">
                                Maintenance mode returns a
                                <strong>503 response</strong> to all visitors.
                                Queue workers and scheduled jobs continue
                                running unaffected. Use the bypass secret to
                                access the app while maintenance is active.
                            </p>
                        </AlertDescription>
                    </Alert>

                    <!-- Status -->
                    <Card>
                        <CardHeader class="pb-3">
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="size-8 rounded-md flex items-center justify-center shrink-0"
                                        :class="
                                            form.maintenance_enabled
                                                ? 'bg-amber-50'
                                                : 'bg-emerald-50'
                                        "
                                    >
                                        <Zap
                                            class="size-3.5"
                                            :class="
                                                form.maintenance_enabled
                                                    ? 'text-amber-600'
                                                    : 'text-emerald-600'
                                            "
                                        />
                                    </div>
                                    <div>
                                        <CardTitle class="text-sm"
                                            >Application Status</CardTitle
                                        >
                                        <CardDescription
                                            >Toggle maintenance mode on or
                                            off</CardDescription
                                        >
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Badge
                                        variant="outline"
                                        :class="
                                            form.maintenance_enabled
                                                ? 'text-amber-600 border-amber-400 bg-amber-50'
                                                : 'text-emerald-600 border-emerald-400 bg-emerald-50'
                                        "
                                    >
                                        <span
                                            class="size-1.5 rounded-full mr-1.5 inline-block"
                                            :class="
                                                form.maintenance_enabled
                                                    ? 'bg-amber-500'
                                                    : 'bg-emerald-500'
                                            "
                                        />
                                        {{
                                            form.maintenance_enabled
                                                ? "Maintenance"
                                                : "Online"
                                        }}
                                    </Badge>
                                    <Switch
                                        v-model:model-value="
                                            form.maintenance_enabled
                                        "
                                    />
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-3 pt-0">
                            <div class="flex items-center gap-3">
                                <span
                                    class="size-2.5 rounded-full shrink-0 ring-4 transition-all duration-300"
                                    :class="
                                        form.maintenance_enabled
                                            ? 'bg-amber-500 ring-amber-100'
                                            : 'bg-emerald-500 ring-emerald-100'
                                    "
                                />
                                <div>
                                    <p class="text-sm font-medium">
                                        {{
                                            form.maintenance_enabled
                                                ? "Maintenance mode is active"
                                                : "Application is online"
                                        }}
                                    </p>
                                    <p
                                        class="text-xs text-muted-foreground mt-0.5"
                                    >
                                        {{
                                            form.maintenance_enabled
                                                ? "Returning HTTP 503 to all visitors. Scheduled tests are paused."
                                                : "All requests are being served normally."
                                        }}
                                    </p>
                                </div>
                            </div>
                            <Alert
                                v-if="form.maintenance_enabled"
                                variant="destructive"
                                class="border-amber-400 bg-amber-50 text-amber-800 [&>svg]:text-amber-600"
                            >
                                <AlertTriangle class="size-4" />
                                <AlertDescription as-child>
                                    <p class="text-muted-foreground text-sm">
                                        {{
                                            form.isDirty
                                                ? "App will be in maintenance mode upon saving. make sure you have your bypass code ready."
                                                : "App is currently in maintenance mode."
                                        }}
                                    </p>
                                </AlertDescription>
                            </Alert>
                        </CardContent>
                    </Card>

                    <!-- Config -->
                    <Card>
                        <CardHeader class="pb-3">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="size-8 rounded-md bg-muted flex items-center justify-center shrink-0"
                                >
                                    <Key
                                        class="size-3.5 text-muted-foreground"
                                    />
                                </div>
                                <div>
                                    <CardTitle class="text-sm"
                                        >Maintenance configuration</CardTitle
                                    >
                                    <CardDescription>
                                        Options passed to
                                        <code class="text-[11px]"
                                            >artisan down</code
                                        >
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 gap-5">
                                <!-- Bypass secret -->
                                <div class="space-y-1.5">
                                    <Label>Bypass secret</Label>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Access the app via
                                        <code class="text-[10px]"
                                            >/?secret=&lt;value&gt;</code
                                        >
                                        during maintenance
                                    </p>
                                    <div class="flex gap-2">
                                        <div class="relative flex-1">
                                            <Input
                                                v-model="form.bypass_secret"
                                                :type="
                                                    showSecret
                                                        ? 'text'
                                                        : 'password'
                                                "
                                                placeholder="Enter or generate a secret"
                                                class="pr-9"
                                            />
                                            <button
                                                type="button"
                                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                                                @click="
                                                    showSecret = !showSecret
                                                "
                                            >
                                                <Eye
                                                    v-if="!showSecret"
                                                    class="size-3.5"
                                                />
                                                <EyeOff
                                                    v-else
                                                    class="size-3.5"
                                                />
                                            </button>
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="generateSecret"
                                        >
                                            <RefreshCw
                                                class="size-3.5 mr-1.5"
                                            />Generate
                                        </Button>
                                    </div>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Leave blank to disable bypass access.
                                    </p>
                                </div>

                                <!-- Retry-After -->
                                <div class="space-y-1.5">
                                    <Label>Retry-After</Label>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Sent in the
                                        <code class="text-[10px]"
                                            >Retry-After</code
                                        >
                                        header of the 503 response
                                    </p>
                                    <div class="flex gap-2">
                                        <Input
                                            v-model.number="
                                                form.retry_after_value
                                            "
                                            type="number"
                                            min="0"
                                            class="w-24"
                                            :class="{
                                                'border-destructive':
                                                    form.errors
                                                        .retry_after_value,
                                            }"
                                        />
                                        <Select v-model="form.retry_after_unit">
                                            <SelectTrigger class="flex-1">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="seconds"
                                                    >seconds</SelectItem
                                                >
                                                <SelectItem value="minutes"
                                                    >minutes</SelectItem
                                                >
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        <template
                                            v-if="
                                                form.retry_after_unit ===
                                                'minutes'
                                            "
                                        >
                                            = {{ retryAfterSeconds }} seconds in
                                            the header
                                        </template>
                                        <template v-else>
                                            Tells clients when to retry the
                                            request.
                                        </template>
                                    </p>
                                </div>

                                <!-- Redirect URL -->
                                <div class="col-span-2 space-y-1.5">
                                    <Label for="maintenance_redirect"
                                        >Redirect to URL</Label
                                    >
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Optional — redirect visitors to an
                                        external status page instead of serving
                                        503
                                    </p>
                                    <Input
                                        id="maintenance_redirect"
                                        v-model="form.maintenance_redirect"
                                        placeholder="https://status.example.com"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Downtime log -->
                    <Card>
                        <CardHeader class="pb-3">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="size-8 rounded-md bg-muted flex items-center justify-center shrink-0"
                                >
                                    <Clock
                                        class="size-3.5 text-muted-foreground"
                                    />
                                </div>
                                <div>
                                    <CardTitle class="text-sm"
                                        >Downtime log</CardTitle
                                    >
                                    <CardDescription
                                        >Recent maintenance
                                        events</CardDescription
                                    >
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="text-[11px] pl-6"
                                            >Event</TableHead
                                        >
                                        <TableHead class="text-[11px]"
                                            >Triggered by</TableHead
                                        >
                                        <TableHead class="text-[11px]"
                                            >Duration</TableHead
                                        >
                                        <TableHead class="text-[11px]"
                                            >Timestamp</TableHead
                                        >
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow
                                        v-for="(log, i) in downtime_logs"
                                        :key="i"
                                    >
                                        <TableCell class="pl-6">
                                            <Badge
                                                variant="outline"
                                                :class="
                                                    log.event === 'DOWN'
                                                        ? 'text-amber-600 border-amber-400 bg-amber-50'
                                                        : 'text-emerald-600 border-emerald-400 bg-emerald-50'
                                                "
                                            >
                                                <span
                                                    class="size-1.5 rounded-full mr-1.5 inline-block"
                                                    :class="
                                                        log.event === 'DOWN'
                                                            ? 'bg-amber-500'
                                                            : 'bg-emerald-500'
                                                    "
                                                />
                                                {{ log.event }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ log.triggered_by }}
                                        </TableCell>
                                        <TableCell class="text-xs">{{
                                            log.duration
                                        }}</TableCell>
                                        <TableCell
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ log.timestamp }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <!-- Footer -->
                    <div class="flex justify-end gap-2 pb-2">
                        <Button
                            variant="outline"
                            :disabled="!form.isDirty || form.processing"
                            @click="discard"
                        >
                            <RotateCcw class="size-3.5 mr-1.5" />Discard
                        </Button>
                        <Button
                            :disabled="!form.isDirty || form.processing"
                            @click="submit"
                        >
                            <Save class="size-3.5 mr-1.5" />
                            {{ form.processing ? "Saving…" : "Save changes" }}
                        </Button>
                    </div>
                </TabsContent>

                <!-- ════════════════════════════════════════════════════
                     DATA RETENTION
                ════════════════════════════════════════════════════ -->
                <TabsContent value="retention" class="mt-5 space-y-4">
                    <Alert>
                        <Info class="size-4" />
                        <AlertDescription as-child>
                            <p class="text-muted-foreground text-sm">
                                Pruning runs as a
                                <strong>scheduled background job</strong>. Only
                                <strong>speed_results</strong> rows older than
                                the configured window are removed. Providers,
                                alert rules, mail providers, webhooks, and users
                                are <strong>never touched</strong>. Minimum
                                retention is 30 days.
                            </p>
                        </AlertDescription>
                    </Alert>

                    <!-- Speed results pruning -->
                    <Card>
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="size-8 rounded-md bg-muted flex items-center justify-center shrink-0"
                                    >
                                        <Activity
                                            class="size-3.5 text-muted-foreground"
                                        />
                                    </div>
                                    <div>
                                        <CardTitle class="text-sm"
                                            >Speed results pruning</CardTitle
                                        >
                                        <CardDescription>
                                            Auto-delete old test records on a
                                            schedule
                                        </CardDescription>
                                    </div>
                                </div>
                                <Badge
                                    variant="outline"
                                    :class="
                                        form.result_auto_purge
                                            ? 'text-emerald-600 border-emerald-400 bg-emerald-50'
                                            : 'text-muted-foreground border-border'
                                    "
                                >
                                    <span
                                        class="size-1.5 rounded-full mr-1.5 inline-block"
                                        :class="
                                            form.result_auto_purge
                                                ? 'bg-emerald-500'
                                                : 'bg-muted-foreground'
                                        "
                                    />
                                    {{
                                        form.result_auto_purge
                                            ? "Enabled"
                                            : "Disabled"
                                    }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <div>
                                    <p class="text-sm font-medium">
                                        Enable automatic pruning
                                    </p>
                                    <p
                                        class="text-xs text-muted-foreground mt-0.5"
                                    >
                                        A scheduled job deletes speed_results
                                        rows older than the retention window
                                    </p>
                                </div>
                                <Switch
                                    v-model:checked="form.result_auto_purge"
                                />
                            </div>

                            <Separator />

                            <div class="grid grid-cols-2 gap-5">
                                <div class="space-y-1.5">
                                    <Label>Retention window</Label>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Results older than this are eligible for
                                        deletion. Minimum 30 days.
                                    </p>
                                    <Select
                                        v-model.number="
                                            form.result_retention_days
                                        "
                                        :disabled="!form.result_auto_purge"
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="opt in retentionOptions"
                                                :key="opt.value"
                                                :value="opt.value"
                                            >
                                                {{ opt.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-1.5">
                                    <Label>Prune schedule</Label>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        When the pruning job fires
                                    </p>
                                    <Select
                                        v-model="form.prune_schedule"
                                        :disabled="!form.result_auto_purge"
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="opt in scheduleOptions"
                                                :key="opt.value"
                                                :value="opt.value"
                                            >
                                                {{ opt.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Input
                                        v-if="form.prune_schedule === 'custom'"
                                        v-model="form.prune_cron"
                                        placeholder="0 2 * * *"
                                        class="mt-2"
                                        :disabled="!form.result_auto_purge"
                                    />
                                </div>

                                <div class="space-y-1.5">
                                    <Label>Batch size</Label>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Rows deleted per iteration. Smaller
                                        values reduce database lock time.
                                    </p>
                                    <div class="relative">
                                        <Input
                                            v-model.number="form.batch_size"
                                            type="number"
                                            min="100"
                                            max="10000"
                                            :disabled="!form.result_auto_purge"
                                            class="pr-20"
                                        />
                                        <span
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-muted-foreground pointer-events-none"
                                        >
                                            rows / batch
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-1.5">
                                    <Label>Exempt failed results</Label>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Keep status = failed rows regardless of
                                        the retention window
                                    </p>
                                    <div class="pt-1">
                                        <Switch
                                            v-model:checked="form.exempt_failed"
                                            :disabled="!form.result_auto_purge"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Retention projection -->
                            <div
                                class="rounded-md bg-muted/60 border border-border/60 p-4 space-y-3"
                            >
                                <p
                                    class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground"
                                >
                                    Retention projection
                                </p>
                                <div
                                    v-for="proj in retention_projections"
                                    :key="proj.table"
                                >
                                    <div class="flex justify-between mb-1.5">
                                        <span class="text-xs font-medium">{{
                                            proj.table
                                        }}</span>
                                        <span
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            {{
                                                proj.current_rows.toLocaleString()
                                            }}
                                            / ~{{
                                                proj.max_rows.toLocaleString()
                                            }}
                                            max
                                        </span>
                                    </div>
                                    <div
                                        class="h-1.5 rounded-full bg-background overflow-hidden mb-1"
                                    >
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="
                                                barColor(
                                                    retentionPct(
                                                        proj.current_rows,
                                                        proj.max_rows,
                                                    ),
                                                )
                                            "
                                            :style="{
                                                width: `${retentionPct(proj.current_rows, proj.max_rows)}%`,
                                            }"
                                        />
                                    </div>
                                    <p
                                        class="text-[10.5px] text-muted-foreground"
                                    >
                                        {{
                                            retentionPct(
                                                proj.current_rows,
                                                proj.max_rows,
                                            )
                                        }}% of {{ proj.window_days }}-day window
                                        used
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Webhook delivery log -->
                    <Card>
                        <CardHeader class="pb-3">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="size-8 rounded-md bg-muted flex items-center justify-center shrink-0"
                                >
                                    <Server
                                        class="size-3.5 text-muted-foreground"
                                    />
                                </div>
                                <div>
                                    <CardTitle class="text-sm"
                                        >Webhook delivery log</CardTitle
                                    >
                                    <CardDescription
                                        >Delivery history
                                        retention</CardDescription
                                    >
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 gap-5">
                                <div class="space-y-1.5">
                                    <Label>Retain delivery logs for</Label>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Minimum 30 days enforced. Applies to all
                                        delivery records.
                                    </p>
                                    <Select
                                        v-model.number="
                                            form.webhook_retention_days
                                        "
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="opt in retentionOptions"
                                                :key="opt.value"
                                                :value="opt.value"
                                            >
                                                {{ opt.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-1.5">
                                    <Label
                                        >Extended retention for failures</Label
                                    >
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Failed delivery records are kept for 90
                                        days regardless of the setting above
                                    </p>
                                    <div class="pt-1">
                                        <Switch
                                            v-model:checked="
                                                form.webhook_extended_retention
                                            "
                                        />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Footer -->
                    <div class="flex justify-end gap-2 pb-2">
                        <Button
                            variant="outline"
                            :disabled="!form.isDirty || form.processing"
                            @click="discard"
                        >
                            <RotateCcw class="size-3.5 mr-1.5" />Discard
                        </Button>
                        <Button
                            :disabled="!form.isDirty || form.processing"
                            @click="submit"
                        >
                            <Save class="size-3.5 mr-1.5" />
                            {{ form.processing ? "Saving…" : "Save changes" }}
                        </Button>
                    </div>
                </TabsContent>

                <!-- ════════════════════════════════════════════════════
                     DANGER ZONE
                ════════════════════════════════════════════════════ -->
                <TabsContent value="danger" class="mt-5 space-y-4">
                    <!-- Cache management — safe, no confirmation needed -->
                    <Card>
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="size-8 rounded-md bg-muted flex items-center justify-center shrink-0"
                                    >
                                        <RefreshCw
                                            class="size-3.5 text-muted-foreground"
                                        />
                                    </div>
                                    <div>
                                        <CardTitle class="text-sm"
                                            >Cache management</CardTitle
                                        >
                                        <CardDescription>
                                            Clear compiled caches without
                                            affecting any stored data
                                        </CardDescription>
                                    </div>
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="clearAllCache"
                                >
                                    <RefreshCw class="size-3.5 mr-1.5" />Clear
                                    all
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent
                            class="divide-y divide-border/60 p-0 px-6 pb-2"
                        >
                            <div
                                v-for="ct in cacheTypes"
                                :key="ct.key"
                                class="flex items-center justify-between gap-4 py-3"
                            >
                                <div>
                                    <p class="text-sm font-medium">
                                        {{ ct.label }}
                                    </p>
                                    <p
                                        class="text-xs text-muted-foreground mt-0.5"
                                    >
                                        {{ ct.desc }}
                                    </p>
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="cacheClearing[ct.key]"
                                    @click="clearCache(ct.key)"
                                >
                                    <Check
                                        v-if="cacheCleared[ct.key]"
                                        class="size-3.5 mr-1.5 text-emerald-600"
                                    />
                                    <RefreshCw
                                        v-else
                                        class="size-3.5 mr-1.5"
                                        :class="{
                                            'animate-spin':
                                                cacheClearing[ct.key],
                                        }"
                                    />
                                    <span
                                        :class="{
                                            'text-emerald-600':
                                                cacheCleared[ct.key],
                                        }"
                                    >
                                        {{
                                            cacheCleared[ct.key]
                                                ? "Cleared"
                                                : cacheClearing[ct.key]
                                                  ? "Clearing…"
                                                  : "Clear"
                                        }}
                                    </span>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Irreversible warning -->
                    <Alert variant="destructive">
                        <AlertTriangle class="size-4" />
                        <AlertDescription>
                            <strong>Actions below are irreversible.</strong>
                            Each operation requires typed confirmation before it
                            can be executed. User accounts and login credentials
                            are never affected.
                        </AlertDescription>
                    </Alert>

                    <!-- Danger actions -->
                    <div class="space-y-3">
                        <div
                            v-for="action in dangerActions"
                            :key="action.key"
                            class="rounded-lg border border-destructive/30 overflow-hidden"
                        >
                            <div
                                class="flex items-start justify-between gap-3 p-4"
                            >
                                <div>
                                    <p
                                        class="text-sm font-semibold text-destructive"
                                    >
                                        {{ action.title }}
                                    </p>
                                    <p
                                        class="text-xs text-muted-foreground mt-1"
                                    >
                                        {{ action.desc }}
                                    </p>
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="border-destructive/40 text-destructive hover:bg-destructive/10 shrink-0"
                                    @click="toggleDanger(action.key)"
                                >
                                    {{
                                        dangerOpen[action.key]
                                            ? "Cancel"
                                            : action.label
                                    }}
                                </Button>
                            </div>
                            <Transition
                                enter-active-class="transition-all duration-200 ease-out"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition-all duration-150 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 -translate-y-1"
                            >
                                <div
                                    v-if="dangerOpen[action.key]"
                                    class="border-t border-destructive/30 bg-destructive/5 p-4 space-y-3"
                                >
                                    <Alert variant="destructive" class="py-2">
                                        <AlertTriangle class="size-3.5" />
                                        <AlertDescription class="text-xs">
                                            {{ action.detail }}
                                        </AlertDescription>
                                    </Alert>
                                    <p class="text-xs text-destructive">
                                        Type
                                        <code
                                            class="bg-destructive/10 px-1 py-0.5 rounded text-[11px]"
                                        >
                                            {{ action.word }}
                                        </code>
                                        to confirm.
                                    </p>
                                    <div class="flex gap-2">
                                        <Input
                                            v-model="dangerConfirm[action.key]"
                                            :placeholder="action.word"
                                            class="h-8 text-xs border-destructive/40 focus-visible:ring-destructive/40"
                                        />
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            :disabled="
                                                dangerConfirm[action.key] !==
                                                    action.word ||
                                                dangerProcessing[action.key]
                                            "
                                            @click="executeDanger(action.key)"
                                        >
                                            <Trash2 class="size-3.5 mr-1.5" />
                                            {{
                                                dangerProcessing[action.key]
                                                    ? "Running…"
                                                    : "Confirm"
                                            }}
                                        </Button>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
