<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { Loader2, Plus } from "@lucide/vue";
import { computed, watch } from "vue";
import ActionRow from "@/components/alert-rule/ActionRow.vue";
import ConditionRow from "@/components/alert-rule/ConditionRow.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import type {
    AlertRule,
    AlertRuleAction,
    AlertRuleCondition,
} from "@/types/alert-rule";
import { EVENT_OPTIONS } from "@/types/alert-rule";
import type { EmailTemplate } from "@/types/email-template";
import type { MailProvider } from "@/types/mail";
import type { Webhook } from "@/types/webhook";

const props = defineProps<{
    rule?: AlertRule | null;
    isNew: boolean;
    providers: Array<{ slug: string; label: string }>;
    mailProviders: MailProvider[];
    emailTemplates: EmailTemplate[];
    webhooks: Webhook[];
}>();

const emit = defineEmits<{
    saved: [];
    cancel: [];
}>();

const form = useForm({
    name: props.rule?.name ?? "",
    provider_slug: props.rule?.provider_slug ?? "",
    event: props.rule?.event ?? "run_completes",
    condition_operator: props.rule?.condition_operator ?? "and",
    is_active: props.rule?.is_active ?? true,
    cooldown_minutes: props.rule?.cooldown_minutes ?? 30,
    conditions: (props.rule?.conditions ?? []) as AlertRuleCondition[],
    actions: (props.rule?.actions ?? []) as AlertRuleAction[],
});

watch(
    () => props.rule,
    (rule) => {
        if (!rule) return;

        form.name = rule.name;
        form.provider_slug = rule.provider_slug ?? "";
        form.event = rule.event;
        form.condition_operator = rule.condition_operator;
        form.is_active = rule.is_active;
        form.cooldown_minutes = rule.cooldown_minutes;
        form.conditions = [...rule.conditions];
        form.actions = [...rule.actions];
    },
    { immediate: false },
);

const addCondition = () => {
    form.conditions.push({
        metric: "status",
        operator: "is",
        value: "failed",
        sort_order: form.conditions.length,
    });
};

const updateCondition = (index: number, updated: AlertRuleCondition) => {
    form.conditions[index] = { ...updated, sort_order: index };
};

const removeCondition = (index: number) => {
    form.conditions.splice(index, 1);
    form.conditions.forEach((c, i) => {
        c.sort_order = i;
    });
};

const addEmailAction = () => {
    form.actions.push({
        type: "email",
        mail_provider_id: null,
        email_template_id: props.emailTemplates[0]?.id ?? null,
        recipient_email: null,
        webhook_id: null,
        sort_order: form.actions.length,
    });
};

const addWebhookAction = () => {
    form.actions.push({
        type: "webhook",
        mail_provider_id: null,
        email_template_id: null,
        recipient_email: null,
        webhook_id: props.webhooks[0]?.id ?? null,
        sort_order: form.actions.length,
    });
};

const updateAction = (index: number, updated: AlertRuleAction) => {
    form.actions[index] = { ...updated, sort_order: index };
};

const removeAction = (index: number) => {
    form.actions.splice(index, 1);
    form.actions.forEach((a, i) => {
        a.sort_order = i;
    });
};

const save = () => {
    const method = props.isNew ? "post" : "patch";
    const routeName = props.isNew
        ? "speedtest.alert-rules.store"
        : "speedtest.alert-rules.update";
    const routeParams = props.isNew ? {} : { alertRule: props.rule!.id };

    // Always send conditions and actions so backend replaces them wholesale
    // This ensures deleted actions/conditions are actually removed
    form[method](route(routeName, routeParams, false), {
        data: {
            name: form.name,
            provider_slug: form.provider_slug || null,
            event: form.event,
            condition_operator: form.condition_operator,
            is_active: form.is_active,
            cooldown_minutes: form.cooldown_minutes,
            conditions: form.conditions,
            actions: form.actions,
        },
        preserveScroll: true,
        onSuccess: () => emit("saved"),
    } as any);
};

const cooldownLabel = computed(() => {
    const m = form.cooldown_minutes;

    if (m === 0) return "No cooldown";

    if (m < 60) return `${m} minutes`;

    if (m === 60) return "1 hour";

    if (m < 1440) return `${Math.floor(m / 60)} hours`;

    return `${Math.floor(m / 1440)} day${Math.floor(m / 1440) > 1 ? "s" : ""}`;
});
</script>

<template>
    <div class="flex flex-1 flex-col overflow-hidden">
        <!-- Top bar -->
        <div
            class="border-border flex items-center justify-between border-b px-4 py-3"
        >
            <div>
                <div class="text-sm font-medium">
                    {{ isNew ? "New alert rule" : rule?.name }}
                </div>
                <div class="text-muted-foreground text-xs">
                    Define when this rule fires and what actions to trigger
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1.5">
                    <Switch
                        :model-value="form.is_active"
                        @update:model-value="(v) => (form.is_active = v)"
                    />
                    <span class="text-muted-foreground text-xs">
                        {{ form.is_active ? "Active" : "Paused" }}
                    </span>
                </div>
                <Button variant="secondary" size="sm" @click="emit('cancel')"
                    >Cancel</Button
                >
                <Button size="sm" :disabled="form.processing" @click="save">
                    <Loader2
                        v-if="form.processing"
                        class="mr-1.5 h-3 w-3 animate-spin"
                    />
                    Save rule
                </Button>
            </div>
        </div>

        <!-- Builder body -->
        <div class="flex flex-1 flex-col gap-3 overflow-y-auto p-4">
            <!-- Rule name -->
            <div class="space-y-1.5">
                <Label class="text-xs">Rule name</Label>
                <Input
                    v-model="form.name"
                    placeholder="e.g. Speedtest failure alert"
                    class="text-xs"
                />
                <p v-if="form.errors.name" class="text-destructive text-xs">
                    {{ form.errors.name }}
                </p>
            </div>

            <!-- WHEN block -->
            <div
                class="overflow-hidden rounded-lg border border-blue-200 dark:border-blue-900"
            >
                <div
                    class="flex items-center gap-2 border-b border-blue-200 bg-blue-50 px-3 py-2 dark:border-blue-900 dark:bg-blue-950/50"
                >
                    <span
                        class="rounded bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                    >
                        When
                    </span>
                    <span class="text-xs text-blue-600 dark:text-blue-400"
                        >trigger event</span
                    >
                </div>
                <div class="flex flex-wrap items-end gap-3 p-3">
                    <div class="space-y-1">
                        <Label class="text-[10px]">Provider</Label>
                        <Select
                            :model-value="form.provider_slug || 'any'"
                            @update:model-value="
                                (v) => {
                                    if (
                                        typeof v === 'string' ||
                                        typeof v === 'number'
                                    ) {
                                        form.provider_slug =
                                            String(v) === 'any'
                                                ? ''
                                                : String(v);
                                    }
                                }
                            "
                        >
                            <SelectTrigger
                                class="h-8 w-auto min-w-[160px] text-xs"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="any" class="text-xs"
                                    >Any provider</SelectItem
                                >
                                <SelectItem
                                    v-for="p in providers"
                                    :key="p.slug"
                                    :value="p.slug"
                                    class="text-xs"
                                >
                                    {{ p.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-1">
                        <Label class="text-[10px]">Event</Label>
                        <Select
                            :model-value="form.event"
                            @update:model-value="
                                (v) => v && (form.event = String(v) as any)
                            "
                        >
                            <SelectTrigger
                                class="h-8 w-auto min-w-[160px] text-xs"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="opt in EVENT_OPTIONS"
                                    :key="opt.value"
                                    :value="opt.value"
                                    class="text-xs"
                                >
                                    {{ opt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            <!-- IF block -->
            <div
                class="overflow-hidden rounded-lg border border-purple-200 dark:border-purple-900"
            >
                <!-- Header row -->
                <div
                    class="border-b border-purple-200 bg-purple-50 px-3 py-2 dark:border-purple-900 dark:bg-purple-950/50"
                >
                    <!-- Top row: label + add button -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span
                                class="rounded bg-purple-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-purple-700 dark:bg-purple-900 dark:text-purple-300"
                            >
                                If
                            </span>
                            <span
                                class="text-xs text-purple-600 dark:text-purple-400"
                                >conditions</span
                            >
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-7 border-purple-300 text-xs text-purple-700 hover:bg-purple-50 dark:border-purple-700 dark:text-purple-300"
                            @click="addCondition"
                        >
                            <Plus class="mr-1 h-3 w-3" />
                            Add condition
                        </Button>
                    </div>

                    <!-- AND / OR toggle — on its own line, clear of border -->
                    <div
                        v-if="form.conditions.length > 1"
                        class="mt-2 flex items-center gap-2"
                    >
                        <div
                            class="flex items-center gap-0.5 rounded-md border border-purple-200 bg-white p-0.5 dark:border-purple-800 dark:bg-purple-950"
                        >
                            <button
                                v-for="op in ['and', 'or']"
                                :key="op"
                                type="button"
                                class="rounded px-2.5 py-0.5 text-[10px] font-medium transition-colors"
                                :class="
                                    form.condition_operator === op
                                        ? 'bg-purple-600 text-white'
                                        : 'text-purple-600 hover:bg-purple-50 dark:text-purple-400 dark:hover:bg-purple-900'
                                "
                                @click="
                                    form.condition_operator = op as 'and' | 'or'
                                "
                            >
                                {{ op.toUpperCase() }}
                            </button>
                        </div>
                        <span
                            class="text-[10px] text-purple-500 dark:text-purple-400"
                        >
                            {{
                                form.condition_operator === "and"
                                    ? "all conditions must match"
                                    : "any condition must match"
                            }}
                        </span>
                    </div>
                </div>

                <!-- Conditions list -->
                <div class="space-y-2 p-3">
                    <div
                        v-if="form.conditions.length === 0"
                        class="text-muted-foreground rounded-lg border border-dashed py-4 text-center text-xs"
                    >
                        No conditions — rule fires on every matching event.
                        <button
                            class="text-primary ml-1 underline"
                            @click="addCondition"
                        >
                            Add one
                        </button>
                    </div>
                    <ConditionRow
                        v-for="(condition, index) in form.conditions"
                        :key="index"
                        :condition="condition"
                        :index="index"
                        @update="updateCondition(index, $event)"
                        @remove="removeCondition(index)"
                    />
                    <p
                        v-if="form.errors.conditions"
                        class="text-destructive text-xs"
                    >
                        {{ form.errors.conditions }}
                    </p>
                </div>
            </div>

            <!-- THEN block -->
            <div
                class="overflow-hidden rounded-lg border border-green-200 dark:border-green-900"
            >
                <div
                    class="flex items-center justify-between border-b border-green-200 bg-green-50 px-3 py-2 dark:border-green-900 dark:bg-green-950/50"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="rounded bg-green-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-green-700 dark:bg-green-900 dark:text-green-300"
                        >
                            Then
                        </span>
                        <span class="text-xs text-green-600 dark:text-green-400"
                            >instant actions</span
                        >
                    </div>
                    <div class="flex gap-1.5">
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-7 border-green-300 text-xs text-green-700 hover:bg-green-50 dark:border-green-700 dark:text-green-300"
                            @click="addEmailAction"
                        >
                            <Plus class="mr-1 h-3 w-3" />
                            Email
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-7 border-green-300 text-xs text-green-700 hover:bg-green-50 dark:border-green-700 dark:text-green-300"
                            @click="addWebhookAction"
                        >
                            <Plus class="mr-1 h-3 w-3" />
                            Webhook
                        </Button>
                    </div>
                </div>
                <div class="space-y-2 p-3">
                    <div
                        v-if="form.actions.length === 0"
                        class="text-muted-foreground rounded-lg border border-dashed py-4 text-center text-xs"
                    >
                        No actions yet. Add an email notification or webhook
                        trigger.
                    </div>
                    <ActionRow
                        v-for="(action, index) in form.actions"
                        :key="index"
                        :action="action"
                        :index="index"
                        :mail-providers="mailProviders"
                        :email-templates="emailTemplates"
                        :webhooks="webhooks"
                        @update="updateAction(index, $event)"
                        @remove="removeAction(index)"
                    />
                    <p
                        v-if="form.errors.actions"
                        class="text-destructive text-xs"
                    >
                        {{ form.errors.actions }}
                    </p>
                </div>
            </div>

            <!-- Cooldown block -->
            <div class="overflow-hidden rounded-lg border border-border">
                <div
                    class="flex items-center gap-2 border-b bg-muted/50 px-3 py-2"
                >
                    <span
                        class="rounded bg-muted px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground"
                    >
                        Cooldown
                    </span>
                    <span class="text-muted-foreground text-xs"
                        >prevent alert spam</span
                    >
                </div>
                <div class="flex flex-wrap items-center gap-2 p-3">
                    <span class="text-xs text-muted-foreground"
                        >Don't re-fire within</span
                    >
                    <Input
                        v-model.number="form.cooldown_minutes"
                        type="number"
                        min="0"
                        max="10080"
                        class="h-8 w-24 text-xs"
                    />
                    <span class="text-xs text-muted-foreground"
                        >minutes of the last trigger</span
                    >
                    <span class="text-muted-foreground text-xs font-medium"
                        >({{ cooldownLabel }})</span
                    >
                </div>
            </div>
        </div>
    </div>
</template>
