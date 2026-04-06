<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import {
    Bell,
    Plus,
    Pencil,
    Trash2,
    CheckCircle2,
    PauseCircle,
} from "lucide-vue-next";
import { ref } from "vue";
import RuleBuilder from "@/components/alert-rule/RuleBuilder.vue";
import {
    AlertDialog,
    AlertDialogAction,
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
import type { AlertRule } from "@/types/alert-rule";
import type { EmailTemplate } from "@/types/email-template";
import type { MailProvider } from "@/types/mail";
import type { Webhook } from "@/types/webhook";

const props = defineProps<{
    rules: AlertRule[];
    providers: Array<{ slug: string; label: string }>;
    mail_providers: MailProvider[];
    email_templates: EmailTemplate[];
    webhooks: Webhook[];
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Settings", href: "#" },
    {
        title: "Alert rules",
        href: route("speedtest.alert-rules.index", {}, false),
    },
];

// ── Builder state ─────────────────────────────────────────────────────────────
const selectedId = ref<string | undefined>(undefined);
const isNew = ref(false);
const showBuilder = ref(false);

const selectedRule = () =>
    props.rules.find((r) => r.id === selectedId.value) ?? null;

function openNew() {
    selectedId.value = undefined;
    isNew.value = true;
    showBuilder.value = true;
}

function openEdit(rule: AlertRule) {
    selectedId.value = rule.id;
    isNew.value = false;
    showBuilder.value = true;
}

function closeBuilder() {
    showBuilder.value = false;
    selectedId.value = undefined;
    isNew.value = false;
}

function onSaved() {
    closeBuilder();
}

// ── Delete ────────────────────────────────────────────────────────────────────
const deleteTarget = ref<AlertRule | null>(null);

function confirmDelete(rule: AlertRule) {
    deleteTarget.value = rule;
}

function doDelete() {
    if (!deleteTarget.value) return;

    router.delete(
        route(
            "speedtest.alert-rules.destroy",
            { alertRule: deleteTarget.value.id },
            false,
        ),
        {
            preserveScroll: true,
            onSuccess: () => {
                deleteTarget.value = null;
            },
        },
    );
}

// ── Toggle ────────────────────────────────────────────────────────────────────
function toggle(rule: AlertRule) {
    router.post(
        route("speedtest.alert-rules.toggle", { alertRule: rule.id }, false),
        {},
        { preserveScroll: true },
    );
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function lastTriggeredLabel(rule: AlertRule): string {
    if (!rule.last_triggered_at) return "Never triggered";

    const diff = Date.now() - new Date(rule.last_triggered_at).getTime();
    const mins = Math.floor(diff / 60_000);

    if (mins < 1) return "Triggered just now";

    if (mins < 60) return `Last triggered ${mins} min ago`;

    const hrs = Math.floor(mins / 60);

    if (hrs < 24) return `Last triggered ${hrs}h ago`;

    return `Last triggered ${Math.floor(hrs / 24)}d ago`;
}

function actionsSummary(rule: AlertRule): string {
    const emails = rule.actions.filter((a) => a.type === "email").length;
    const webhooks = rule.actions.filter((a) => a.type === "webhook").length;
    const parts = [];

    if (emails) parts.push(`${emails} email${emails > 1 ? "s" : ""}`);

    if (webhooks) parts.push(`${webhooks} webhook${webhooks > 1 ? "s" : ""}`);

    return parts.join(" + ") || "No actions";
}
</script>

<template>
    <Head title="Alert rules" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 flex-1">
            <!-- ── Left: rules list ── -->
            <div
                class="border-border flex flex-col border-r transition-all"
                :class="showBuilder ? 'w-72 shrink-0' : 'flex-1'"
            >
                <!-- Header -->
                <div
                    class="border-border flex items-center justify-between border-b px-4 py-3"
                >
                    <div>
                        <h1 class="text-sm font-medium">Alert rules</h1>
                        <p class="text-muted-foreground text-xs">
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

                <!-- Rules list -->
                <div class="flex-1 overflow-y-auto">
                    <!-- Empty state -->
                    <div
                        v-if="rules.length === 0"
                        class="flex flex-col items-center justify-center gap-3 py-16"
                    >
                        <div
                            class="bg-muted flex h-12 w-12 items-center justify-center rounded-full"
                        >
                            <Bell class="text-muted-foreground h-5 w-5" />
                        </div>
                        <p class="text-muted-foreground text-sm">
                            No alert rules yet.
                        </p>
                        <Button size="sm" variant="outline" @click="openNew">
                            <Plus class="mr-1.5 h-4 w-4" />
                            Create your first rule
                        </Button>
                    </div>

                    <!-- Rule cards -->
                    <div
                        v-for="rule in rules"
                        :key="rule.id"
                        class="border-border cursor-pointer border-b p-4 transition-colors"
                        :class="{
                            'border-l-2 border-l-primary bg-primary/5':
                                showBuilder && selectedId === rule.id,
                            'hover:bg-muted/50': !(
                                showBuilder && selectedId === rule.id
                            ),
                        }"
                        @click="openEdit(rule)"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span
                                        class="text-sm font-medium truncate"
                                        >{{ rule.name }}</span
                                    >
                                    <Badge
                                        variant="outline"
                                        class="shrink-0 text-[10px]"
                                        :class="
                                            rule.is_active
                                                ? 'border-green-300 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-300'
                                                : 'border-border bg-muted text-muted-foreground'
                                        "
                                    >
                                        {{
                                            rule.is_active ? "active" : "paused"
                                        }}
                                    </Badge>
                                </div>

                                <!-- Summary badges -->
                                <div class="mt-1.5 flex flex-wrap gap-1">
                                    <span
                                        class="inline-flex items-center rounded bg-blue-50 px-1.5 py-0.5 text-[10px] text-blue-700 dark:bg-blue-950 dark:text-blue-300"
                                    >
                                        {{
                                            rule.provider_slug ?? "Any provider"
                                        }}
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded bg-purple-50 px-1.5 py-0.5 text-[10px] text-purple-700 dark:bg-purple-950 dark:text-purple-300"
                                    >
                                        {{ rule.event_label }}
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded bg-green-50 px-1.5 py-0.5 text-[10px] text-green-700 dark:bg-green-950 dark:text-green-300"
                                    >
                                        {{ actionsSummary(rule) }}
                                    </span>
                                </div>

                                <p
                                    class="text-muted-foreground mt-1 text-[10px]"
                                >
                                    {{ lastTriggeredLabel(rule) }}
                                    <template v-if="rule.cooldown_minutes > 0">
                                        · {{ rule.cooldown_minutes }}min
                                        cooldown
                                    </template>
                                </p>
                            </div>

                            <!-- Actions (stop propagation) -->
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
                                        class="h-3.5 w-3.5 text-green-500"
                                    />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7"
                                    @click="openEdit(rule)"
                                >
                                    <Pencil
                                        class="text-muted-foreground h-3.5 w-3.5"
                                    />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="text-muted-foreground hover:text-destructive h-7 w-7"
                                    @click="confirmDelete(rule)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-border border-t px-4 py-2.5">
                    <p class="text-muted-foreground text-[10px]">
                        Rules are evaluated after every speedtest result is
                        recorded. Actions fire immediately when all conditions
                        match.
                    </p>
                </div>
            </div>

            <!-- ── Right: builder ── -->
            <div
                v-if="showBuilder"
                class="flex flex-1 flex-col overflow-hidden"
            >
                <RuleBuilder
                    :key="isNew ? 'new' : selectedId"
                    :rule="isNew ? null : selectedRule()"
                    :is-new="isNew"
                    :providers="providers"
                    :mail-providers="mail_providers"
                    :email-templates="email_templates"
                    :webhooks="webhooks"
                    @saved="onSaved"
                    @cancel="closeBuilder"
                />
            </div>

            <!-- No rule selected placeholder -->
            <div
                v-else-if="rules.length > 0"
                class="text-muted-foreground flex flex-1 flex-col items-center justify-center gap-2"
            >
                <Bell class="h-8 w-8 opacity-20" />
                <p class="text-sm">
                    Select a rule to edit or create a new one.
                </p>
            </div>
        </div>
    </AppLayout>

    <!-- Delete confirm -->
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
                <AlertDialogAction
                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    @click="doDelete"
                >
                    Delete rule
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
