<script setup lang="ts">
import { Button } from "@/components/ui/button";
import { MAIL_DRIVERS } from "@/types/mail";
import type { MailDriver } from "@/types/mail";

defineProps<{
    modelValue: MailDriver | null;
}>();

const emit = defineEmits<{
    "update:modelValue": [value: MailDriver];
}>();

const driverIcons: Record<MailDriver, string> = {
    smtp: "📧",
    resend: "✉️",
    mailgun: "📮",
    postmark: "📬",
    ses: "☁️",
    sendmail: "📨",
};
</script>

<template>
    <div class="grid grid-cols-3 gap-2">
        <Button
            v-for="driver in MAIL_DRIVERS"
            :key="driver.value"
            type="button"
            variant="outline"
            class="h-auto items-start justify-start gap-2.5 p-3 text-left transition-colors"
            :class="
                modelValue === driver.value
                    ? 'border-primary bg-primary/5 text-primary hover:bg-primary/10 hover:text-primary'
                    : 'border-border hover:border-primary/40 hover:bg-muted/50'
            "
            @click="emit('update:modelValue', driver.value)"
        >
            <span class="mt-0.5 text-base leading-none">{{
                driverIcons[driver.value]
            }}</span>
            <div>
                <div
                    class="text-xs font-medium"
                    :class="
                        modelValue === driver.value
                            ? 'text-primary'
                            : 'text-foreground'
                    "
                >
                    {{ driver.label }}
                </div>
                <div class="text-muted-foreground mt-0.5 text-[10px]">
                    {{ driver.description }}
                </div>
            </div>
        </Button>
    </div>
</template>
