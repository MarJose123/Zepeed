<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import { CheckCircle2, Loader2, Plus, Trash2, XCircle } from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
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
import type { Webhook } from "@/types/webhook";

const props = defineProps<{
    open: boolean;
    webhook?: Webhook | null;
}>();

const emit = defineEmits<{
    "update:open": [value: boolean];
}>();

const isEdit = computed(() => !!props.webhook);

const testProcessing = ref(false);
const testResult = ref<"success" | "failed" | null>(null);
const testMessage = ref<string | null>(null);

const form = useForm({
    name: "",
    url: "",
    method: "POST" as Webhook["method"],
    secret: "",
    headers: [] as Array<{ key: string; value: string }>,
    timeout: 30,
    retry_attempts: 3,
    verify_ssl: true,
    is_active: true,
});

// Populate form when editing
watch(
    () => props.webhook,
    (wh) => {
        if (!wh) {
            return;
        }

        form.name = wh.name;
        form.url = wh.url;
        form.method = wh.method;
        form.secret = ""; // never pre-fill secret
        form.headers = [...wh.headers];
        form.timeout = wh.timeout;
        form.retry_attempts = wh.retry_attempts;
        form.verify_ssl = wh.verify_ssl;
        form.is_active = wh.is_active;
    },
    { immediate: true },
);

watch(
    () => props.open,
    () => {
        testResult.value = null;
        testMessage.value = null;
    },
);

function addHeader() {
    form.headers.push({ key: "", value: "" });
}

function removeHeader(index: number) {
    form.headers.splice(index, 1);
}

function generateSecret() {
    const chars =
        "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    form.secret =
        "whsec_" +
        Array.from(
            { length: 32 },
            () => chars[Math.floor(Math.random() * chars.length)],
        ).join("");
}

function close() {
    emit("update:open", false);
    setTimeout(() => {
        form.reset();
        testResult.value = null;
        testMessage.value = null;
    }, 200);
}

function testConnection() {
    if (!props.webhook) {
        return;
    }

    testProcessing.value = true;
    testResult.value = null;
    testMessage.value = null;

    router.post(
        route(
            "speedtest.integration.webhooks.test",
            { webhook: props.webhook.id },
            false,
        ),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                testResult.value = "success";
                testMessage.value =
                    "Test delivery succeeded — endpoint responded successfully.";
            },
            onError: () => {
                testResult.value = "failed";
                testMessage.value =
                    "Test delivery failed — check your URL and try again.";
            },
            onFinish: () => {
                testProcessing.value = false;
            },
        },
    );
}

function save() {
    const routeName = isEdit.value
        ? "speedtest.integration.webhooks.update"
        : "speedtest.integration.webhooks.store";
    const routeParams = isEdit.value ? { webhook: props.webhook!.id } : {};

    const method = isEdit.value ? "patch" : "post";

    form[method](route(routeName, routeParams, false), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            close();
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="close">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle class="text-sm font-medium">
                    {{ isEdit ? `Edit — ${webhook?.name}` : "Add webhook" }}
                </DialogTitle>
            </DialogHeader>

            <div class="space-y-3">
                <!-- Name -->
                <div class="space-y-1.5">
                    <Label class="text-xs">Name</Label>
                    <Input
                        v-model="form.name"
                        placeholder="e.g. Slack alerts"
                        class="text-xs"
                    />
                    <p v-if="form.errors.name" class="text-destructive text-xs">
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- Method + Timeout -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <Label class="text-xs">Method</Label>
                        <Select v-model="form.method">
                            <SelectTrigger class="text-xs"
                                ><SelectValue
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="POST">POST</SelectItem>
                                <SelectItem value="GET">GET</SelectItem>
                                <SelectItem value="PUT">PUT</SelectItem>
                                <SelectItem value="PATCH">PATCH</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs">Timeout (seconds)</Label>
                        <Input
                            v-model.number="form.timeout"
                            type="number"
                            min="1"
                            max="120"
                            class="text-xs"
                        />
                    </div>
                </div>

                <!-- URL -->
                <div class="space-y-1.5">
                    <Label class="text-xs">URL</Label>
                    <Input
                        v-model="form.url"
                        placeholder="https://hooks.slack.com/services/…"
                        class="font-mono text-xs"
                    />
                    <p v-if="form.errors.url" class="text-destructive text-xs">
                        {{ form.errors.url }}
                    </p>
                </div>

                <Separator />

                <!-- Signing secret -->
                <div class="space-y-1.5">
                    <Label class="text-xs">
                        Signing secret
                        <span class="text-muted-foreground font-normal"
                            >(optional)</span
                        >
                    </Label>
                    <div class="flex gap-2">
                        <Input
                            v-model="form.secret"
                            :placeholder="
                                isEdit && webhook?.has_secret
                                    ? 'Leave blank to keep existing secret'
                                    : 'whsec_••••••••••••'
                            "
                            class="flex-1 font-mono text-xs"
                        />
                        <Button
                            variant="outline"
                            size="sm"
                            @click="generateSecret"
                            >Generate</Button
                        >
                    </div>
                    <div
                        class="inline-flex items-center gap-1.5 rounded-md bg-purple-50 px-2 py-1 font-mono text-[10px] text-purple-700 dark:bg-purple-950 dark:text-purple-300"
                    >
                        <svg
                            class="h-3 w-3 shrink-0"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <rect x="3" y="11" width="18" height="11" rx="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                        Signed via X-Signature-256
                    </div>
                </div>

                <!-- Retry + SSL -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <Label class="text-xs">Retry attempts</Label>
                        <Select
                            :model-value="String(form.retry_attempts)"
                            @update:model-value="
                                (v) =>
                                    v !== null &&
                                    (form.retry_attempts = Number(v))
                            "
                        >
                            <SelectTrigger class="text-xs"
                                ><SelectValue
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="0">No retry</SelectItem>
                                <SelectItem value="3">3 attempts</SelectItem>
                                <SelectItem value="5">5 attempts</SelectItem>
                                <SelectItem value="10">10 attempts</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs">Verify SSL</Label>
                        <div class="flex h-9 items-center gap-2">
                            <Switch
                                :model-value="form.verify_ssl"
                                @update:model-value="
                                    (v) => (form.verify_ssl = v)
                                "
                            />
                            <span class="text-muted-foreground text-xs">
                                {{
                                    form.verify_ssl
                                        ? "Enabled (recommended)"
                                        : "Disabled"
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- Custom headers -->
                <div class="space-y-2">
                    <Label class="text-xs">
                        Custom headers
                        <span class="text-muted-foreground font-normal"
                            >(optional)</span
                        >
                    </Label>
                    <div
                        v-for="(header, index) in form.headers"
                        :key="index"
                        class="flex gap-2"
                    >
                        <Input
                            v-model="header.key"
                            placeholder="Authorization"
                            class="flex-1 text-xs"
                        />
                        <Input
                            v-model="header.value"
                            placeholder="Bearer token…"
                            class="flex-1 text-xs"
                        />
                        <Button
                            variant="ghost"
                            size="icon"
                            class="text-muted-foreground hover:text-destructive h-9 w-9 shrink-0"
                            @click="removeHeader(index)"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        class="text-xs"
                        @click="addHeader"
                    >
                        <Plus class="mr-1.5 h-3.5 w-3.5" />
                        Add header
                    </Button>
                </div>

                <!-- Test connection result -->
                <Transition
                    enter-active-class="transition-all duration-200"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                >
                    <div
                        v-if="testResult !== null"
                        class="flex items-start gap-2 rounded-md border p-3 text-xs"
                        :class="
                            testResult === 'success'
                                ? 'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-950'
                                : 'border-destructive/30 bg-destructive/5'
                        "
                    >
                        <CheckCircle2
                            v-if="testResult === 'success'"
                            class="mt-px h-4 w-4 shrink-0 text-green-600"
                        />
                        <XCircle
                            v-else
                            class="text-destructive mt-px h-4 w-4 shrink-0"
                        />
                        <p
                            :class="
                                testResult === 'success'
                                    ? 'text-green-700 dark:text-green-400'
                                    : 'text-destructive'
                            "
                        >
                            {{ testMessage }}
                        </p>
                    </div>
                </Transition>
            </div>

            <DialogFooter
                class="flex items-center justify-between sm:justify-between pt-2"
            >
                <Button variant="secondary" size="sm" @click="close"
                    >Cancel</Button
                >
                <div class="flex items-center gap-2">
                    <!-- Test button — only active when editing an existing saved webhook -->
                    <Button
                        v-if="isEdit"
                        variant="outline"
                        size="sm"
                        :disabled="testProcessing"
                        @click="testConnection"
                    >
                        <Loader2
                            v-if="testProcessing"
                            class="mr-1.5 h-3 w-3 animate-spin"
                        />
                        {{ testProcessing ? "Testing…" : "Test connection" }}
                    </Button>
                    <Button size="sm" :disabled="form.processing" @click="save">
                        <Loader2
                            v-if="form.processing"
                            class="mr-1.5 h-3 w-3 animate-spin"
                        />
                        {{ isEdit ? "Save changes" : "Save webhook" }}
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
