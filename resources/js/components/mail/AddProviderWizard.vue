<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import { Loader2 } from "lucide-vue-next";
import { ref, computed } from "vue";
import DriverConfigForm from "@/components/mail/DriverConfigForm.vue";
import DriverPicker from "@/components/mail/DriverPicker.vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Separator } from "@/components/ui/separator";
import type { MailDriver } from "@/types/mail";

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    "update:open": [value: boolean];
}>();

type Step = 1 | 2 | 3;

const step = ref<Step>(1);
const testResult = ref<"success" | "failed" | null>(null);
const testProcessing = ref(false);
const testErrorMsg = ref<string | null>(null);
const savedId = ref<string | null>(null);

const form = useForm({
    driver: null as MailDriver | null,
    label: "",
    from_address: "noreply@zepeed.local",
    from_name: "Zepeed",
    config: {} as Record<string, string>,
    test_email: "",
});

// ── Step label ────────────────────────────────────────────────────────────────
const stepLabel = computed(
    () =>
        ({
            1: "Choose provider",
            2: `Configure ${driverLabel.value}`,
            3: `Test ${driverLabel.value} connection`,
        })[step.value],
);

// ── Driver helpers ─────────────────────────────────────────────────────────────
const driverLabel = computed(() => {
    const map: Record<MailDriver, string> = {
        smtp: "SMTP",
        resend: "Resend",
        mailgun: "Mailgun",
        postmark: "Postmark",
        ses: "Amazon SES",
        sendmail: "Sendmail",
    };

    return form.driver ? map[form.driver] : "";
});

// Label placeholder — personalized per driver
const labelPlaceholder = computed(() => {
    const map: Record<MailDriver, string> = {
        smtp: "e.g. Office365 SMTP, Gmail SMTP",
        resend: "e.g. Resend production",
        mailgun: "e.g. Mailgun US",
        postmark: "e.g. Postmark transactional",
        ses: "e.g. AWS SES us-east-1",
        sendmail: "e.g. Server sendmail",
    };

    return form.driver ? map[form.driver] : "Provider label";
});

// From address placeholder — personalized per driver
const fromAddressPlaceholder = computed(() => {
    const map: Record<MailDriver, string> = {
        smtp: "noreply@yourdomain.com",
        resend: "noreply@yourdomain.com",
        mailgun: "noreply@mg.yourdomain.com",
        postmark: "noreply@yourdomain.com",
        ses: "noreply@verified-domain.com",
        sendmail: "noreply@yourdomain.com",
    };

    return form.driver ? map[form.driver] : "noreply@yourdomain.com";
});

// From address hint — extra context per driver
const fromAddressHint = computed(() => {
    if (form.driver === "ses") {
        return "Must be a verified identity in your AWS SES account.";
    }

    if (form.driver === "mailgun") {
        return "Must match your Mailgun sending domain.";
    }

    if (form.driver === "postmark") {
        return "Must be a verified sender signature in Postmark.";
    }

    return null;
});

// ── Step guards ────────────────────────────────────────────────────────────────
const canProceedStep1 = computed(() => form.driver !== null);

const canProceedStep2 = computed(
    () =>
        form.label.trim() !== "" &&
        form.from_address.trim() !== "" &&
        form.from_name.trim() !== "" &&
        Object.keys(form.config).length > 0,
);

// ── Navigation ─────────────────────────────────────────────────────────────────
function next() {
    if (step.value < 3) {
        step.value = (step.value + 1) as Step;
    }
}

function back() {
    if (step.value > 1) {
        step.value = (step.value - 1) as Step;
    }

    testResult.value = null;
    testErrorMsg.value = null;
}

function close() {
    emit("update:open", false);
    setTimeout(() => {
        step.value = 1;
        testResult.value = null;
        testErrorMsg.value = null;
        savedId.value = null;
        form.reset();
    }, 200);
}

// ── Save then test ─────────────────────────────────────────────────────────────
// We save first to get the provider ID, then fire the real test endpoint.
// The provider is removed if the user closes without confirming.
function saveAndProceedToTest() {
    form.post(route("speedtest.integration.smtp.store", {}, false), {
        preserveScroll: true,
        onSuccess: () => {
            step.value = 3;
        },
        onFlash: (params) => {
            savedId.value = params.mail_provider_id as string;
        },
    });
}

// ── Real backend test ─────────────────────────────────────────────────────────
function sendTest() {
    if (!form.test_email.trim() || !savedId.value || testProcessing.value) {
        return;
    }

    testProcessing.value = true;
    testResult.value = null;
    testErrorMsg.value = null;

    router.post(
        route(
            "speedtest.integration.smtp.test",
            { mailProvider: savedId.value },
            false,
        ),
        { email: form.test_email },
        {
            preserveScroll: true,
            onSuccess: () => {
                testResult.value = "success";
            },
            onError: (errors) => {
                testResult.value = "failed";
                testErrorMsg.value =
                    Object.values(errors)[0] ?? "Connection failed.";
            },
            onFinish: () => {
                testProcessing.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog :open="open" @update:open="close">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle class="text-sm font-medium"
                    >Add mail provider</DialogTitle
                >
            </DialogHeader>

            <!-- Step indicator -->
            <div class="flex items-center px-1">
                <template v-for="n in [1, 2, 3]" :key="n">
                    <div
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-medium transition-colors"
                        :class="{
                            'bg-primary text-primary-foreground': step === n,
                            'bg-green-600 text-white': step > n,
                            'bg-muted text-muted-foreground border border-border':
                                step < n,
                        }"
                    >
                        <span v-if="step > n">✓</span>
                        <span v-else>{{ n }}</span>
                    </div>
                    <div
                        v-if="n < 3"
                        class="mx-2 h-px flex-1 transition-colors"
                        :class="step > n ? 'bg-green-600' : 'bg-border'"
                    />
                </template>
            </div>

            <p class="text-muted-foreground text-xs">
                Step {{ step }} of 3 — {{ stepLabel }}
            </p>

            <Separator />

            <!-- Step 1: Pick driver -->
            <div v-if="step === 1" class="space-y-3">
                <DriverPicker v-model="form.driver" />
            </div>

            <!-- Step 2: Configure -->
            <div v-else-if="step === 2" class="space-y-3">
                <!-- Label — personalized placeholder -->
                <div class="space-y-1.5">
                    <Label class="text-xs">Provider label</Label>
                    <Input
                        v-model="form.label"
                        :placeholder="labelPlaceholder"
                        class="text-xs"
                    />
                    <p
                        v-if="form.errors.label"
                        class="text-destructive text-xs"
                    >
                        {{ form.errors.label }}
                    </p>
                </div>

                <Separator />

                <!-- Driver-specific config fields -->
                <DriverConfigForm
                    v-if="form.driver"
                    :driver="form.driver"
                    :config="form.config"
                    @update:config="form.config = $event"
                />

                <Separator />

                <!-- From address + name -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <Label class="text-xs">From address</Label>
                        <Input
                            v-model="form.from_address"
                            type="email"
                            :placeholder="fromAddressPlaceholder"
                            class="text-xs"
                        />
                        <p
                            v-if="fromAddressHint"
                            class="text-muted-foreground text-[10px]"
                        >
                            {{ fromAddressHint }}
                        </p>
                        <p
                            v-if="form.errors.from_address"
                            class="text-destructive text-xs"
                        >
                            {{ form.errors.from_address }}
                        </p>
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs">From name</Label>
                        <Input
                            v-model="form.from_name"
                            placeholder="Zepeed"
                            class="text-xs"
                        />
                        <p
                            v-if="form.errors.from_name"
                            class="text-destructive text-xs"
                        >
                            {{ form.errors.from_name }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 3: Test & save -->
            <div v-else-if="step === 3" class="space-y-3">
                <div class="space-y-1.5">
                    <Label class="text-xs">Send test email to</Label>
                    <div class="flex gap-2">
                        <Input
                            v-model="form.test_email"
                            type="email"
                            placeholder="admin@mail.com"
                            class="flex-1 text-xs"
                            @keydown.enter="sendTest"
                        />
                        <Button
                            size="sm"
                            variant="outline"
                            :disabled="
                                !form.test_email.trim() ||
                                testProcessing ||
                                !savedId
                            "
                            @click="sendTest"
                        >
                            <Loader2
                                v-if="testProcessing"
                                class="mr-1.5 h-3 w-3 animate-spin"
                            />
                            {{ testProcessing ? "Sending…" : "Send test" }}
                        </Button>
                    </div>
                    <p class="text-muted-foreground text-[10px]">
                        A real email will be sent via {{ driverLabel }} to
                        verify your configuration.
                    </p>
                </div>

                <!-- Success result -->
                <div
                    v-if="testResult === 'success'"
                    class="flex items-start gap-2 rounded-md border border-green-300 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950"
                >
                    <span class="mt-px text-green-600">✓</span>
                    <div>
                        <p
                            class="text-xs font-medium text-green-700 dark:text-green-400"
                        >
                            Connection successful
                        </p>
                        <p
                            class="text-[10px] text-green-600 dark:text-green-500"
                        >
                            Test email delivered via {{ driverLabel }} —
                            configuration is valid.
                        </p>
                    </div>
                </div>

                <!-- Failed result -->
                <div
                    v-else-if="testResult === 'failed'"
                    class="flex items-start gap-2 rounded-md border border-destructive/30 bg-destructive/5 p-3"
                >
                    <span class="text-destructive mt-px">✕</span>
                    <div>
                        <p class="text-destructive text-xs font-medium">
                            Connection failed
                        </p>
                        <p class="text-destructive/70 text-[10px]">
                            {{
                                testErrorMsg ??
                                "Check your configuration and try again."
                            }}
                        </p>
                    </div>
                </div>

                <!-- Neutral hint when not yet tested -->
                <p
                    v-if="testResult === null && !testProcessing"
                    class="text-muted-foreground text-[10px]"
                >
                    Provider has been saved. You can close and test later from
                    the provider list, or send a test now to verify before
                    finishing.
                </p>
            </div>

            <!-- Footer -->
            <DialogFooter
                class="flex items-center justify-between gap-2 sm:justify-between"
            >
                <Button
                    v-if="step > 1"
                    variant="secondary"
                    size="sm"
                    :disabled="step === 3"
                    @click="back"
                >
                    ← Back
                </Button>
                <div v-else />

                <div class="flex items-center gap-2">
                    <Button variant="secondary" size="sm" @click="close">
                        {{ step === 3 ? "Close" : "Cancel" }}
                    </Button>

                    <!-- Step 1 → 2 -->
                    <Button
                        v-if="step === 1"
                        size="sm"
                        :disabled="!canProceedStep1"
                        @click="next"
                    >
                        Next →
                    </Button>

                    <!-- Step 2 → save + go to step 3 -->
                    <Button
                        v-else-if="step === 2"
                        size="sm"
                        :disabled="!canProceedStep2 || form.processing"
                        @click="saveAndProceedToTest"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="mr-1.5 h-3 w-3 animate-spin"
                        />
                        Save & test →
                    </Button>

                    <!-- Step 3 — done -->
                    <Button v-else-if="step === 3" size="sm" @click="close">
                        Done
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
