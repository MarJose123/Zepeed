<script setup lang="ts">
import { Head, router, usePage } from "@inertiajs/vue3";
import {
    Bell,
    Plus,
    Pencil,
    Trash2,
    CheckCircle2,
    PauseCircle,
} from "lucide-vue-next";
import { ref, watch } from "vue";
import RuleBuilder from "@/components/alert-rule/RuleBuilder.vue";
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

const page = usePage();
const selectedId = ref<string | undefined | null>(null);
const isNew = ref(false);
const showBuilder = ref(false);
const pendingSaveName = ref<string | null>(null);
const pendingSaveIsNew = ref(false);

const selectedRule = () =>
    props.rules.find((r) => r.id === selectedId.value) ?? null;

const openNew = () => {
    selectedId.value = null;
    isNew.value = true;
    showBuilder.value = true;
}

const openEdit = (rule: AlertRule) => {
    selectedId.value = rule.id;
    isNew.value = false;
    showBuilder.value = true;
}

const closeBuilder = () => {
    showBuilder.value = false;
    selectedId.value = null;
    isNew.value = false;
}

const onSaved = () => {
    // Capture what we need before the Inertia reload resets things
    pendingSaveIsNew.value = isNew.value;
    pendingSaveName.value = null; // will use ID for edits, name for new

    if (!isNew.value && selectedId.value) {
        // For edits — keep selectedId, Inertia reload will refresh the rule data
        pendingSaveName.value = null;
    } else {
        // For new rules — mark so the watcher picks the latest
        pendingSaveName.value = "__new__";
    }
}

// Watch for rules to reload after Inertia redirect, then re-select
watch(
    () => props.rules,
    (newRules) => {
        if (pendingSaveName.value === null && selectedId.value) {
            // Edit case — rule already selected, just refresh it in place
            // showBuilder stays true, selectedId stays the same
            // The RuleBuilder :key stays as selectedId so it re-renders with fresh data
            return;
        }

        if (pendingSaveName.value === "__new__") {
            // New rule case — select the most recently created (first in list since ordered latest())
            const newest = newRules[0];

            if (newest) {
                selectedId.value = newest.id;
                isNew.value = false;
                showBuilder.value = true;
                pendingSaveName.value = null;
            }
        }
    },
    { deep: false },
);

watch(
    () => page.flash?.alert_rule_id as string | null,
    (id) => {
        if (!id) return;

        // Keep builder open, pointing at the saved rule (new or edited)
        selectedId.value = id;
        isNew.value = false;
        showBuilder.value = true;
    },
    { immediate: true },
);

const deleteTarget = ref<AlertRule | null>(null);

const confirmDelete = (rule: AlertRule) => {
    deleteTarget.value = rule;
}

const doDelete = () => {
    if (!deleteTarget.value) return;

    const target = deleteTarget.value;

    router.delete(
        route("speedtest.alert-rules.destroy", { alertRule: target.id }, false),
        {
            preserveScroll: true,
            preserveState: false, // force full reload so rules list refreshes
            onSuccess: () => {
                // Close builder if we just deleted the selected rule
                if (selectedId.value === target.id) {
                    closeBuilder();
                }
            },
        },
    );

    deleteTarget.value = null; // close dialog immediately
}

const toggle = (rule: AlertRule) => {
    router.post(
        route("speedtest.alert-rules.toggle", { alertRule: rule.id }, false),
        {},
        { preserveScroll: true },
    );
}

const lastTriggeredLabel = (rule: AlertRule): string => {
    if (!rule.last_triggered_at) return "Never triggered";

    const diff = Date.now() - new Date(rule.last_triggered_at).getTime();
    const mins = Math.floor(diff / 60_000);

    if (mins < 1) return "Triggered just now";

    if (mins < 60) return `Last triggered ${mins} min ago`;

    const hrs = Math.floor(mins / 60);

    if (hrs < 24) return `Last triggered ${hrs}h ago`;

    return `Last triggered ${Math.floor(hrs / 24)}d ago`;
}

const actionsSummary = (rule: AlertRule): string => {
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
                                    title="Edit rule"
                                    @click="openEdit(rule)"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                </Button>

                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="text-destructive hover:bg-destructive/10 h-7 w-7"
                                    title="Delete rule"
                                    @click.stop="confirmDelete(rule)"
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
                    :key="isNew ? 'new' : (selectedId ?? 'none')"
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
    <AlertDialog :open="!!deleteTarget" @update:open="(v) => !v">
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
                <Button variant="destructive" size="sm" @click="doDelete">
                    Delete rule
                </Button>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
