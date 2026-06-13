<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { Loader2, Plus } from "@lucide/vue";
import { computed, watch } from "vue";
import PingAlertActionRow from "@/components/network/PingAlertActionRow.vue";
import PingAlertConditionRow from "@/components/network/PingAlertConditionRow.vue";
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
import type { EmailTemplate } from "@/types/email-template";
import type { MailProvider } from "@/types/mail";
import type {
    PingAlertAction,
    PingAlertCondition,
    PingAlertRule,
    PingTarget,
} from "@/types/ping";
import type { Webhook } from "@/types/webhook";

const props = defineProps<{
    rule: PingAlertRule | null;
    isNew: boolean;
    targets: PingTarget[];
    mailProviders: MailProvider[];
    emailTemplates: EmailTemplate[];
    webhooks: Webhook[];
}>();

const emit = defineEmits<{ cancel: [] }>();

const form = useForm({
    name: props.rule?.name ?? "",
    ping_target_id: props.rule?.ping_target_id ?? "",
    condition_operator: props.rule?.condition_operator ?? "and",
    is_active: props.rule?.is_active ?? true,
    cooldown_minutes: props.rule?.cooldown_minutes ?? 30,
    conditions: (props.rule?.conditions ?? []) as PingAlertCondition[],
    actions: (props.rule?.actions ?? []) as PingAlertAction[],
});

watch(
    () => props.rule,
    (rule) => {
        if (!rule) return;

        form.name = rule.name;
        form.ping_target_id = rule.ping_target_id;
        form.condition_operator = rule.condition_operator;
        form.is_active = rule.is_active;
        form.cooldown_minutes = rule.cooldown_minutes;
        form.conditions = [...rule.conditions];
        form.actions = [...rule.actions];
    },
);

const cooldownLabel = computed(() => {
    const m = form.cooldown_minutes;

    if (m === 0) return "no cooldown";

    if (m < 60) return `${m} minutes`;

    if (m === 60) return "1 hour";

    if (m % 60 === 0) return `${m / 60} hours`;

    return `${m} minutes`;
});

const addCondition = () => {
    form.conditions.push({
        metric: "latency_avg",
        operator: "is_above",
        value: "50",
        lookback_minutes: 5,
        sort_order: form.conditions.length,
    });
};

const updateCondition = (i: number, updated: PingAlertCondition) => {
    form.conditions[i] = { ...updated, sort_order: i };
};

const removeCondition = (i: number) => {
    form.conditions.splice(i, 1);
};

const addEmailAction = () => {
    form.actions.push({
        type: "email",
        mail_provider_id: null,
        email_template_id: null,
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
        webhook_id: null,
        sort_order: form.actions.length,
    });
};

const updateAction = (i: number, updated: PingAlertAction) => {
    form.actions[i] = { ...updated, sort_order: i };
};

const removeAction = (i: number) => {
    form.actions.splice(i, 1);
};

const submit = () => {
    if (props.isNew) {
        form.post(route("speedtest.network.ping-alerts.store", {}, false), {
            preserveScroll: true,
        });
    } else {
        form.patch(
            route(
                "speedtest.network.ping-alerts.update",
                { pingAlertRule: props.rule!.id },
                false,
            ),
            { preserveScroll: true },
        );
    }
};
</script>

<template>
    <div class="flex h-full flex-col overflow-hidden">
        <!-- Header -->
        <div
            class="flex items-center justify-between border-b border-border px-5 py-3"
        >
            <div>
                <h2 class="text-sm font-medium">
                    {{ isNew ? "Create Alert Rule" : `Edit: ${rule?.name}` }}
                </h2>
                <p class="text-xs text-muted-foreground">
                    Trigger notifications when ping conditions are met
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Switch v-model:checked="form.is_active" />
                <span class="text-xs text-muted-foreground">{{
                    form.is_active ? "Active" : "Paused"
                }}</span>
            </div>
        </div>

        <!-- Body -->
        <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
            <!-- Name -->
            <div class="space-y-1.5">
                <Label>Rule Name <span class="text-destructive">*</span></Label>
                <Input
                    v-model="form.name"
                    placeholder="e.g. High latency on Primary DNS"
                    class="h-9"
                />
                <p v-if="form.errors.name" class="text-xs text-destructive">
                    {{ form.errors.name }}
                </p>
            </div>

            <!-- Target -->
            <div class="space-y-1.5">
                <Label>Target <span class="text-destructive">*</span></Label>
                <Select v-model="form.ping_target_id">
                    <SelectTrigger class="h-9"
                        ><SelectValue placeholder="Select a ping target"
                    /></SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="t in targets"
                            :key="t.id"
                            :value="t.id"
                        >
                            {{ t.label }}
                            <span class="ml-1 text-xs text-muted-foreground">{{
                                t.host
                            }}</span>
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p
                    v-if="form.errors.ping_target_id"
                    class="text-xs text-destructive"
                >
                    {{ form.errors.ping_target_id }}
                </p>
            </div>

            <!-- When block -->
            <div
                class="overflow-hidden rounded-lg border border-blue-200 dark:border-blue-900"
            >
                <div
                    class="flex items-center justify-between border-b border-blue-200 bg-blue-50 px-3 py-2 dark:border-blue-900 dark:bg-blue-950/50"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="rounded bg-blue-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                            >When</span
                        >
                        <Select
                            :model-value="form.condition_operator"
                            @update:model-value="
                                (v) =>
                                    v &&
                                    (form.condition_operator = v as
                                        | 'and'
                                        | 'or')
                            "
                        >
                            <SelectTrigger
                                class="h-6 border-0 bg-transparent p-0 text-xs text-blue-600 shadow-none focus:ring-0 dark:text-blue-400 w-auto"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="and" class="text-xs"
                                    >all conditions match</SelectItem
                                >
                                <SelectItem value="or" class="text-xs"
                                    >any condition matches</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        class="h-7 border-blue-300 text-xs text-blue-700 hover:bg-blue-50 dark:border-blue-700 dark:text-blue-300"
                        :disabled="form.conditions.length >= 5"
                        @click="addCondition"
                    >
                        <Plus class="mr-1 h-3 w-3" />
                        Add condition
                    </Button>
                </div>

                <div class="space-y-2 p-3">
                    <div
                        v-if="form.conditions.length === 0"
                        class="rounded-lg border border-dashed py-4 text-center text-xs text-muted-foreground"
                    >
                        No conditions yet. Add one to get started.
                    </div>
                    <PingAlertConditionRow
                        v-for="(cond, i) in form.conditions"
                        :key="i"
                        :condition="cond"
                        :index="i"
                        @update="updateCondition(i, $event)"
                        @remove="removeCondition(i)"
                    />
                    <p
                        v-if="form.errors.conditions"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.conditions }}
                    </p>
                </div>
            </div>

            <!-- Then block -->
            <div
                class="overflow-hidden rounded-lg border border-green-200 dark:border-green-900"
            >
                <div
                    class="flex items-center justify-between border-b border-green-200 bg-green-50 px-3 py-2 dark:border-green-900 dark:bg-green-950/50"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="rounded bg-green-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-green-700 dark:bg-green-900 dark:text-green-300"
                            >Then</span
                        >
                        <span class="text-xs text-green-600 dark:text-green-400"
                            >instant actions</span
                        >
                    </div>
                    <div class="flex gap-1.5">
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-7 border-green-300 text-xs text-green-700 hover:bg-green-50 dark:border-green-700 dark:text-green-300"
                            :disabled="form.actions.length >= 3"
                            @click="addEmailAction"
                        >
                            <Plus class="mr-1 h-3 w-3" />
                            Email
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-7 border-green-300 text-xs text-green-700 hover:bg-green-50 dark:border-green-700 dark:text-green-300"
                            :disabled="form.actions.length >= 3"
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
                        class="rounded-lg border border-dashed py-4 text-center text-xs text-muted-foreground"
                    >
                        No actions yet. Add an email notification or webhook
                        trigger.
                    </div>
                    <PingAlertActionRow
                        v-for="(action, i) in form.actions"
                        :key="i"
                        :action="action"
                        :index="i"
                        :mail-providers="mailProviders"
                        :email-templates="emailTemplates"
                        :webhooks="webhooks"
                        @update="updateAction(i, $event)"
                        @remove="removeAction(i)"
                    />
                    <p
                        v-if="form.errors.actions"
                        class="text-xs text-destructive"
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
                        >Cooldown</span
                    >
                    <span class="text-xs text-muted-foreground"
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
                        max="1440"
                        class="h-8 w-24 text-xs"
                    />
                    <span class="text-xs text-muted-foreground"
                        >minutes of the last trigger</span
                    >
                    <span class="text-xs font-medium text-muted-foreground"
                        >({{ cooldownLabel }})</span
                    >
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div
            class="flex items-center justify-end gap-2 border-t border-border px-5 py-3"
        >
            <Button variant="outline" size="sm" @click="emit('cancel')"
                >Cancel</Button
            >
            <Button size="sm" :disabled="form.processing" @click="submit">
                <Loader2
                    v-if="form.processing"
                    class="mr-1.5 h-4 w-4 animate-spin"
                />
                {{ isNew ? "Create Rule" : "Save Changes" }}
            </Button>
        </div>
    </div>
</template>
