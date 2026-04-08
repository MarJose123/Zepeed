<script setup lang="ts">
import { Mail, Link2, Trash2 } from "lucide-vue-next";
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
import type { AlertRuleAction } from "@/types/alert-rule";
import type { EmailTemplate } from "@/types/email-template";
import type { MailProvider } from "@/types/mail";
import type { Webhook } from "@/types/webhook";

const props = defineProps<{
    action: AlertRuleAction;
    mailProviders: MailProvider[];
    emailTemplates: EmailTemplate[];
    webhooks: Webhook[];
    index: number;
}>();

const emit = defineEmits<{
    update: [action: AlertRuleAction];
    remove: [];
}>();

const update = (key: keyof AlertRuleAction, value: string | null) => {
    emit("update", { ...props.action, [key]: value });
};
</script>

<template>
    <div
        class="rounded-lg border p-3"
        :class="
            action.type === 'email'
                ? 'border-blue-200 bg-blue-50/50 dark:border-blue-900 dark:bg-blue-950/30'
                : 'border-green-200 bg-green-50/50 dark:border-green-900 dark:bg-green-950/30'
        "
    >
        <div class="mb-2.5 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div
                    class="flex h-6 w-6 items-center justify-center rounded-md"
                    :class="
                        action.type === 'email'
                            ? 'bg-blue-100 dark:bg-blue-900'
                            : 'bg-green-100 dark:bg-green-900'
                    "
                >
                    <Mail
                        v-if="action.type === 'email'"
                        class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400"
                    />
                    <Link2
                        v-else
                        class="h-3.5 w-3.5 text-green-600 dark:text-green-400"
                    />
                </div>
                <span class="text-xs font-medium">
                    {{
                        action.type === "email"
                            ? "Send email notification"
                            : "Trigger webhook"
                    }}
                </span>
            </div>
            <Button
                variant="ghost"
                size="icon"
                class="text-muted-foreground hover:text-destructive h-6 w-6"
                @click="emit('remove')"
            >
                <Trash2 class="h-3 w-3" />
            </Button>
        </div>

        <!-- Email action fields -->
        <div v-if="action.type === 'email'" class="grid grid-cols-3 gap-2">
            <div class="space-y-1">
                <Label class="text-[10px]">Mail provider</Label>
                <Select
                    :model-value="action.mail_provider_id ?? 'auto'"
                    @update:model-value="
                        (v) => {
                            if (
                                typeof v === 'string' ||
                                typeof v === 'number'
                            ) {
                                update(
                                    'mail_provider_id',
                                    String(v) === 'auto' ? null : String(v),
                                );
                            }
                        }
                    "
                >
                    <SelectTrigger class="h-7 text-xs">
                        <SelectValue placeholder="Auto (failover)" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="auto" class="text-xs">
                            Auto (failover chain)
                        </SelectItem>
                        <SelectItem
                            v-for="mp in mailProviders"
                            :key="mp.id"
                            :value="mp.id"
                            class="text-xs"
                        >
                            {{ mp.label }}
                            <span
                                class="text-muted-foreground ml-1 text-[10px]"
                            >
                                {{ mp.is_primary ? "· primary" : "" }}
                            </span>
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="space-y-1">
                <Label class="text-[10px]">Email template</Label>
                <Select
                    :model-value="action.email_template_id ?? 'none'"
                    @update:model-value="
                        (v) => {
                            if (
                                typeof v === 'string' ||
                                typeof v === 'number'
                            ) {
                                update(
                                    'email_template_id',
                                    String(v) === 'none' ? null : String(v),
                                );
                            }
                        }
                    "
                >
                    <SelectTrigger class="h-7 text-xs">
                        <SelectValue placeholder="Select template" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="tpl in emailTemplates"
                            :key="tpl.id"
                            :value="tpl.id"
                            class="text-xs"
                        >
                            {{ tpl.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="space-y-1">
                <Label class="text-[10px]">Recipient email</Label>
                <Input
                    :model-value="action.recipient_email ?? ''"
                    type="email"
                    placeholder="admin@mail.com"
                    class="h-7 text-xs"
                    @update:model-value="
                        (v) => update('recipient_email', String(v) || null)
                    "
                />
            </div>
        </div>

        <!-- Webhook action fields -->
        <div v-else class="space-y-1">
            <Label class="text-[10px]">Webhook</Label>
            <Select
                :model-value="action.webhook_id ?? 'none'"
                @update:model-value="
                    (v) => {
                        if (typeof v === 'string' || typeof v === 'number') {
                            update(
                                'webhook_id',
                                String(v) === 'none' ? null : String(v),
                            );
                        }
                    }
                "
            >
                <SelectTrigger class="h-7 text-xs">
                    <SelectValue placeholder="Select webhook" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="wh in webhooks"
                        :key="wh.id"
                        :value="wh.id"
                        class="text-xs"
                    >
                        {{ wh.name }}
                        <span
                            class="text-muted-foreground ml-1 font-mono text-[10px]"
                        >
                            {{ wh.method }}
                        </span>
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
    </div>
</template>
