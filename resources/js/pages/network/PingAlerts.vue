<script setup lang="ts">
import { Head, router, usePage } from "@inertiajs/vue3";
import {
    Bell,
    CheckCircle2,
    PauseCircle,
    Pencil,
    Plus,
    Trash2,
} from "@lucide/vue";
import { ref, watch } from "vue";
import PingAlertRuleBuilder from "@/components/network/PingAlertRuleBuilder.vue";
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type { EmailTemplate } from "@/types/email-template";
import type { MailProvider } from "@/types/mail";
import type { PingAlertRule, PingTarget } from "@/types/ping";
import type { Webhook } from "@/types/webhook";

const props = defineProps<{
    rules: PingAlertRule[];
    targets: PingTarget[];
    mail_providers: MailProvider[];
    email_templates: EmailTemplate[];
    webhooks: Webhook[];
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Network", href: "#" },
    {
        title: "Ping Alerts",
        href: route("speedtest.network.ping-alerts.index", {}, false),
    },
];

const page = usePage();
const selectedId = ref<string | null>(null);
const isNew = ref(false);
const showBuilder = ref(false);
const deleteTarget = ref<PingAlertRule | null>(null);

const selectedRule = () =>
    props.rules.find((r) => r.id === selectedId.value) ?? null;

const openNew = () => {
    selectedId.value = null;
    isNew.value = true;
    showBuilder.value = true;
};

const openEdit = (rule: PingAlertRule) => {
    selectedId.value = rule.id;
    isNew.value = false;
    showBuilder.value = true;
};

const closeBuilder = () => {
    showBuilder.value = false;
    selectedId.value = null;
    isNew.value = false;
};

watch(
    () => page.flash?.ping_alert_rule_id as string | null,
    (id) => {
        if (!id) return;

        selectedId.value = id;
        isNew.value = false;
        showBuilder.value = true;
    },
    { immediate: true },
);

const toggle = (rule: PingAlertRule) => {
    router.post(
        route(
            "speedtest.network.ping-alerts.toggle",
            { pingAlertRule: rule.id },
            false,
        ),
        {},
        { preserveScroll: true },
    );
};

const doDelete = () => {
    if (!deleteTarget.value) return;

    const target = deleteTarget.value;
    router.delete(
        route(
            "speedtest.network.ping-alerts.destroy",
            { pingAlertRule: target.id },
            false,
        ),
        {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                if (selectedId.value === target.id) closeBuilder();
            },
        },
    );
    deleteTarget.value = null;
};

const lastTriggeredLabel = (rule: PingAlertRule): string => {
    if (!rule.last_triggered_at) return "Never triggered";

    const diff = Date.now() - new Date(rule.last_triggered_at).getTime();
    const mins = Math.floor(diff / 60_000);

    if (mins < 1) return "Triggered just now";

    if (mins < 60) return `Last triggered ${mins}m ago`;

    const hrs = Math.floor(mins / 60);

    return hrs < 24
        ? `Last triggered ${hrs}h ago`
        : `Last triggered ${Math.floor(hrs / 24)}d ago`;
};

const actionsSummary = (rule: PingAlertRule): string => {
    const emails = rule.actions.filter((a) => a.type === "email").length;
    const webhooks = rule.actions.filter((a) => a.type === "webhook").length;
    const parts = [];

    if (emails) parts.push(`${emails} email${emails > 1 ? "s" : ""}`);

    if (webhooks) parts.push(`${webhooks} webhook${webhooks > 1 ? "s" : ""}`);

    return parts.join(" + ") || "No actions";
};
</script>

<template>
    <Head title="Ping Alert Rules" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 flex-1">
            <!-- Left: rule list -->
            <div
                class="flex flex-col border-r border-border transition-all"
                :class="showBuilder ? 'w-80 shrink-0' : 'w-2xl'"
            >
                <div
                    class="flex items-center justify-between border-b border-border px-4 py-3"
                >
                    <div>
                        <h1 class="text-sm font-medium">Alert Rules</h1>
                        <p class="text-xs text-muted-foreground">
                            {{ rules.length }} rule{{
                                rules.length !== 1 ? "s" : ""
                            }}
                            configured
                        </p>
                    </div>
                    <Button size="sm" @click="openNew">
                        <Plus class="mr-1.5 h-4 w-4" />
                        New rule
                    </Button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div
                        v-if="rules.length === 0"
                        class="flex flex-col items-center justify-center gap-3 py-16"
                    >
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-muted"
                        >
                            <Bell class="h-5 w-5 text-muted-foreground" />
                        </div>
                        <p class="text-sm text-muted-foreground">
                            No alert rules yet.
                        </p>
                        <Button size="sm" variant="outline" @click="openNew">
                            <Plus class="mr-1.5 h-4 w-4" />
                            Create your first rule
                        </Button>
                    </div>

                    <div
                        v-for="rule in rules"
                        :key="rule.id"
                        class="cursor-pointer border-b border-border p-4 transition-colors"
                        :class="{
                            'border-l-[3px] border-l-primary bg-primary/5':
                                showBuilder && selectedId === rule.id,
                            'hover:bg-muted/50': !(
                                showBuilder && selectedId === rule.id
                            ),
                        }"
                        @click="openEdit(rule)"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="truncate text-sm font-medium"
                                        >{{ rule.name }}</span
                                    >
                                    <Badge
                                        variant="outline"
                                        class="shrink-0 text-[10px]"
                                        :class="
                                            rule.is_active
                                                ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                : 'border-border bg-muted text-muted-foreground'
                                        "
                                    >
                                        {{
                                            rule.is_active ? "active" : "paused"
                                        }}
                                    </Badge>
                                </div>

                                <div class="mt-1.5 flex flex-wrap gap-1">
                                    <span
                                        class="inline-flex items-center rounded bg-blue-50 px-1.5 py-0.5 text-[10px] text-blue-700 dark:bg-blue-950 dark:text-blue-300"
                                    >
                                        {{ rule.target_label ?? "—" }}
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded bg-purple-50 px-1.5 py-0.5 text-[10px] text-purple-700 dark:bg-purple-950 dark:text-purple-300"
                                    >
                                        {{ rule.conditions.length }} condition{{
                                            rule.conditions.length !== 1
                                                ? "s"
                                                : ""
                                        }}
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded bg-green-50 px-1.5 py-0.5 text-[10px] text-green-700 dark:bg-green-950 dark:text-green-300"
                                    >
                                        {{ actionsSummary(rule) }}
                                    </span>
                                </div>

                                <p
                                    class="mt-1 text-[10px] text-muted-foreground"
                                >
                                    {{ lastTriggeredLabel(rule) }}
                                    <template v-if="rule.cooldown_minutes > 0">
                                        · {{ rule.cooldown_minutes }}min
                                        cooldown
                                    </template>
                                </p>
                            </div>

                            <div
                                class="flex shrink-0 items-center gap-1"
                                @click.stop
                            >
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7"
                                    :title="
                                        rule.is_active
                                            ? 'Pause rule'
                                            : 'Activate rule'
                                    "
                                    @click="toggle(rule)"
                                >
                                    <PauseCircle
                                        v-if="rule.is_active"
                                        class="h-3.5 w-3.5 text-amber-500"
                                    />
                                    <CheckCircle2
                                        v-else
                                        class="h-3.5 w-3.5 text-emerald-500"
                                    />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7"
                                    title="Edit rule"
                                    @click="openEdit(rule)"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7 text-destructive hover:bg-destructive/10"
                                    title="Delete rule"
                                    @click.stop="deleteTarget = rule"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-border px-4 py-2.5">
                    <p class="text-[10px] text-muted-foreground">
                        Rules are evaluated after every ping test. Actions fire
                        when all conditions match.
                    </p>
                </div>
            </div>

            <!-- Right: builder -->
            <div
                v-if="showBuilder"
                class="flex flex-1 flex-col overflow-hidden"
            >
                <PingAlertRuleBuilder
                    :key="isNew ? 'new' : (selectedId ?? 'none')"
                    :rule="isNew ? null : selectedRule()"
                    :is-new="isNew"
                    :targets="targets"
                    :mail-providers="mail_providers"
                    :email-templates="email_templates"
                    :webhooks="webhooks"
                    @cancel="closeBuilder"
                />
            </div>

            <div
                v-else-if="rules.length > 0"
                class="flex flex-1 flex-col items-center justify-center gap-2 text-muted-foreground"
            >
                <Bell class="h-8 w-8 opacity-20" />
                <p class="text-sm">
                    Select a rule to edit or create a new one.
                </p>
            </div>
        </div>
    </AppLayout>

    <AlertDialog
        :open="!!deleteTarget"
        @update:open="(v) => !v && (deleteTarget = null)"
    >
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Delete alert rule?</AlertDialogTitle>
                <AlertDialogDescription>
                    Delete <strong>{{ deleteTarget?.name }}</strong
                    >? This action cannot be undone.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="deleteTarget = null"
                    >Cancel</AlertDialogCancel
                >
                <Button variant="destructive" size="sm" @click="doDelete"
                    >Delete rule</Button
                >
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
