<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
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
    Trash2,
    Zap,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
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
    app_env: props.settings.app_env,
    timezone: props.settings.timezone,
    maintenance_enabled: props.settings.maintenance_enabled,
    bypass_secret: props.settings.bypass_secret,
    retry_after_value: props.settings.retry_after_value,
    retry_after_unit: props.settings.retry_after_unit,
    maintenance_redirect: props.settings.maintenance_redirect,
    result_auto_purge: props.settings.result_auto_purge,
    result_retention_days: props.settings.result_retention_days,
    exempt_failed: props.settings.exempt_failed,
    webhook_retention_days: props.settings.webhook_retention_days,
    webhook_extended_retention: props.settings.webhook_extended_retention,
    prune_frequency: props.settings.prune_frequency,
    prune_hour: props.settings.prune_hour,
    prune_day_of_week: props.settings.prune_day_of_week,
    prune_day_of_month: props.settings.prune_day_of_month,
});

const submit = () => {
    form.patch(route("speedtest.general-settings.update"), {
        preserveScroll: true,
    });
};

const discard = () => form.reset();

const goToPage = (page: number) => {
    router.visit(route("speedtest.general-settings.edit"), {
        method: "get",
        data: { downtime_page: page },
        preserveScroll: true,
        preserveState: true,
        only: ["downtime_logs"],
    });
};

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

const envOptions = [
    { value: "production", label: "production" },
    { value: "local", label: "local" },
    { value: "staging", label: "staging" },
];

const pruneFrequencyOptions = [
    { value: "daily", label: "Daily" },
    { value: "weekly", label: "Weekly" },
    { value: "monthly", label: "Monthly" },
];

const pruneHourOptions = Array.from({ length: 24 }, (_, i) => ({
    value: i,
    label: String(i).padStart(2, "0") + ":00",
}));

const pruneDayOfWeekOptions = [
    { value: 0, label: "Sunday" },
    { value: 1, label: "Monday" },
    { value: 2, label: "Tuesday" },
    { value: 3, label: "Wednesday" },
    { value: 4, label: "Thursday" },
    { value: 5, label: "Friday" },
    { value: 6, label: "Saturday" },
];

const pruneDayOfMonthOptions = Array.from({ length: 28 }, (_, i) => ({
    value: i + 1,
    label:
        i + 1 === 1
            ? "1st"
            : i + 1 === 2
              ? "2nd"
              : i + 1 === 3
                ? "3rd"
                : `${i + 1}th`,
}));

// Human-readable summary shown below the selectors:
const pruneScheduleSummary = computed(() => {
    const h = String(form.prune_hour).padStart(2, "0") + ":00";

    if (form.prune_frequency === "weekly") {
        const day =
            pruneDayOfWeekOptions[form.prune_day_of_week]?.label ?? "Sunday";

        return `Every ${day} at ${h}`;
    }

    if (form.prune_frequency === "monthly") {
        const day =
            pruneDayOfMonthOptions[form.prune_day_of_month - 1]?.label ?? "1st";

        return `Monthly on the ${day} at ${h}`;
    }

    return `Every day at ${h}`;
});
</script>

<template>
    <Head title="General Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
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
                <Badge
                    v-if="form.isDirty"
                    variant="outline"
                    class="text-amber-600 border-amber-400 bg-amber-50"
                >
                    <span
                        class="size-1.5 rounded-full mr-1.5 inline-block bg-amber-500"
                    />
                    Unsaved changes
                </Badge>
            </div>

            <!-- ── Tabs ─────────────────────────────────────────────── -->
            <Tabs default-value="application">
                <TabsList>
                    <TabsTrigger value="application">
                        <Globe class="size-3.5 mr-1.5" />
                        Application
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
                     APPLICATION
                ════════════════════════════════════════════════════ -->

                <TabsContent value="application" class="mt-5 space-y-4">
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
                                    <CardDescription>
                                        Identity and display preferences —
                                        applied globally on next boot
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-5">
                            <Alert>
                                <Info class="size-4" />
                                <AlertDescription as-child>
                                    <p class="text-sm text-muted-foreground">
                                        Changes to <strong>App URL</strong> and
                                        <strong>Timezone</strong>
                                        take effect on the next application
                                        boot. The current session continues
                                        using the previous values.
                                    </p>
                                </AlertDescription>
                            </Alert>

                            <div class="space-y-4 max-w-md">
                                <!-- App URL -->
                                <Field>
                                    <FieldContent>
                                        <FieldLabel for="app_url"
                                            >App URL</FieldLabel
                                        >
                                        <FieldDescription
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            Used in notification links, email
                                            headers, and share URLs
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
                                        <FieldError v-if="form.errors.app_url">
                                            {{ form.errors.app_url }}
                                        </FieldError>
                                    </FieldContent>
                                </Field>

                                <!-- Timezone -->
                                <Field>
                                    <FieldContent>
                                        <FieldLabel for="timezone"
                                            >Timezone</FieldLabel
                                        >
                                        <FieldDescription
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            All timestamps, schedules, and
                                            displayed dates
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
                                        <FieldError v-if="form.errors.timezone">
                                            {{ form.errors.timezone }}
                                        </FieldError>
                                    </FieldContent>
                                </Field>

                                <!-- Environment -->
                                <Field>
                                    <FieldContent>
                                        <FieldLabel for="app_env"
                                            >Environment</FieldLabel
                                        >
                                        <FieldDescription
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            Controls debug output, error detail,
                                            and caching behaviour
                                        </FieldDescription>
                                        <Select v-model="form.app_env">
                                            <SelectTrigger id="app_env">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="opt in envOptions"
                                                    :key="opt.value"
                                                    :value="opt.value"
                                                >
                                                    {{ opt.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p
                                            class="text-[11px] text-muted-foreground mt-1"
                                        >
                                            Set to
                                            <code class="text-[10px]"
                                                >production</code
                                            >
                                            to disable stack traces in live
                                            deployments.
                                        </p>
                                        <FieldError v-if="form.errors.app_env">
                                            {{ form.errors.app_env }}
                                        </FieldError>
                                    </FieldContent>
                                </Field>
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

                    <!-- Status card -->
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
                                                ? "App will enter maintenance mode upon saving. Make sure your bypass secret is set."
                                                : "App is currently in maintenance mode."
                                        }}
                                    </p>
                                </AlertDescription>
                            </Alert>
                        </CardContent>
                    </Card>

                    <!-- Config card -->
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
                                <div class="space-y-1.5">
                                    <Label>Bypass secret</Label>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Access the app via
                                        <code class="text-[10px]"
                                            >/?secret=&lt;value&gt;</code
                                        >
                                        during maintenance. Auto-generated if
                                        left blank.
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
                                                placeholder="Leave blank to auto-generate on save"
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
                                        <template
                                            v-if="
                                                form.maintenance_enabled &&
                                                settings.bypass_secret
                                            "
                                        >
                                            Bypass URL:
                                            <code
                                                class="text-[10px] select-all"
                                            >
                                                {{ settings.app_url }}?secret={{
                                                    settings.bypass_secret
                                                }}
                                            </code>
                                        </template>
                                        <template v-else>
                                            Leave blank — a secret will be
                                            auto-generated when maintenance is
                                            enabled.
                                        </template>
                                    </p>
                                </div>

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
                            <div class="flex items-center justify-between">
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
                                        <CardDescription>
                                            Recent maintenance events
                                            <span
                                                class="text-muted-foreground/60"
                                            >
                                                ·
                                                {{ downtime_logs.total }} total
                                            </span>
                                        </CardDescription>
                                    </div>
                                </div>
                                <!-- Pagination controls -->
                                <div
                                    v-if="downtime_logs.last_page > 1"
                                    class="flex items-center gap-1"
                                >
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="h-7 w-7 p-0"
                                        :disabled="
                                            downtime_logs.current_page === 1
                                        "
                                        @click="goToPage(1)"
                                    >
                                        <ChevronsLeft class="size-3.5" />
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="h-7 w-7 p-0"
                                        :disabled="
                                            downtime_logs.current_page === 1
                                        "
                                        @click="
                                            goToPage(
                                                downtime_logs.current_page - 1,
                                            )
                                        "
                                    >
                                        <ChevronLeft class="size-3.5" />
                                    </Button>
                                    <span
                                        class="text-xs text-muted-foreground px-1.5"
                                    >
                                        {{ downtime_logs.current_page }} /
                                        {{ downtime_logs.last_page }}
                                    </span>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="h-7 w-7 p-0"
                                        :disabled="
                                            downtime_logs.current_page ===
                                            downtime_logs.last_page
                                        "
                                        @click="
                                            goToPage(
                                                downtime_logs.current_page + 1,
                                            )
                                        "
                                    >
                                        <ChevronRight class="size-3.5" />
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="h-7 w-7 p-0"
                                        :disabled="
                                            downtime_logs.current_page ===
                                            downtime_logs.last_page
                                        "
                                        @click="
                                            goToPage(downtime_logs.last_page)
                                        "
                                    >
                                        <ChevronsRight class="size-3.5" />
                                    </Button>
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
                                        v-if="downtime_logs.data.length === 0"
                                    >
                                        <TableCell
                                            colspan="4"
                                            class="pl-6 py-6 text-center text-sm text-muted-foreground"
                                        >
                                            No maintenance events recorded yet.
                                        </TableCell>
                                    </TableRow>
                                    <TableRow
                                        v-for="(log, i) in downtime_logs.data"
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
                                        <TableCell class="text-xs">
                                            {{ log.duration ?? "—" }}
                                        </TableCell>
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
                                    v-model:model-value="form.result_auto_purge"
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
                                        <SelectTrigger
                                            ><SelectValue
                                        /></SelectTrigger>
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
                                    <Label>Exempt failed results</Label>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Keep status = failed rows regardless of
                                        the retention window
                                    </p>
                                    <div class="pt-1">
                                        <Switch
                                            v-model:model-value="
                                                form.exempt_failed
                                            "
                                            :disabled="!form.result_auto_purge"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-2 space-y-1.5">
                                <Label>Prune schedule</Label>
                                <p class="text-[11px] text-muted-foreground">
                                    When the pruning job fires
                                </p>

                                <div class="flex items-center gap-2 flex-wrap">
                                    <!-- Frequency -->
                                    <Select
                                        v-model="form.prune_frequency"
                                        :disabled="!form.result_auto_purge"
                                    >
                                        <SelectTrigger class="w-32">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="opt in pruneFrequencyOptions"
                                                :key="opt.value"
                                                :value="opt.value"
                                            >
                                                {{ opt.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>

                                    <!-- Day of week — only when weekly -->
                                    <template
                                        v-if="form.prune_frequency === 'weekly'"
                                    >
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >on</span
                                        >
                                        <Select
                                            v-model.number="
                                                form.prune_day_of_week
                                            "
                                            :disabled="!form.result_auto_purge"
                                        >
                                            <SelectTrigger class="w-36">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="opt in pruneDayOfWeekOptions"
                                                    :key="opt.value"
                                                    :value="opt.value"
                                                >
                                                    {{ opt.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </template>

                                    <!-- Day of month — only when monthly -->
                                    <template
                                        v-if="
                                            form.prune_frequency === 'monthly'
                                        "
                                    >
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >on the</span
                                        >
                                        <Select
                                            v-model.number="
                                                form.prune_day_of_month
                                            "
                                            :disabled="!form.result_auto_purge"
                                        >
                                            <SelectTrigger class="w-28">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent class="max-h-52">
                                                <SelectItem
                                                    v-for="opt in pruneDayOfMonthOptions"
                                                    :key="opt.value"
                                                    :value="opt.value"
                                                >
                                                    {{ opt.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </template>

                                    <!-- Hour — always shown -->
                                    <span class="text-xs text-muted-foreground"
                                        >at</span
                                    >
                                    <Select
                                        v-model.number="form.prune_hour"
                                        :disabled="!form.result_auto_purge"
                                    >
                                        <SelectTrigger class="w-24">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent class="max-h-52">
                                            <SelectItem
                                                v-for="opt in pruneHourOptions"
                                                :key="opt.value"
                                                :value="opt.value"
                                            >
                                                {{ opt.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Live summary -->
                                <p class="text-[11px] text-muted-foreground">
                                    <span class="text-emerald-600">→</span>
                                    {{ pruneScheduleSummary }}
                                </p>
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
                                        <SelectTrigger
                                            ><SelectValue
                                        /></SelectTrigger>
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
                                            v-model:model-value="
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
                    <!-- Cache management -->
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
                                        <AlertDescription class="text-xs">{{
                                            action.detail
                                        }}</AlertDescription>
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
