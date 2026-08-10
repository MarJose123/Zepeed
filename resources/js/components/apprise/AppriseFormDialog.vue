<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import { Loader2 } from "@lucide/vue";
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
import { Separator } from "@/components/ui/separator";
import { Switch } from "@/components/ui/switch";
import type { Apprise } from "@/types/apprise";

const props = defineProps<{
    open: boolean;
    apprise?: Apprise | null;
}>();

const emit = defineEmits<{
    "update:open": [value: boolean];
}>();

const isEdit = computed(() => !!props.apprise);

const testProcessing = ref(false);
const testResult = ref<"success" | "failed" | null>(null);
const testMessage = ref<string | null>(null);

// Tags are edited as a comma-separated string and split on save.
const tagsText = ref("");

const form = useForm({
    name: "",
    url: "",
    tags: [] as string[],
    username: "",
    password: "",
    timeout: 30,
    verify_ssl: true,
    is_active: true,
});

// Populate form when editing
watch(
    () => props.apprise,
    (a) => {
        if (!a) {
            return;
        }

        form.name = a.name;
        form.url = a.url;
        form.username = a.username ?? "";
        form.password = ""; // never pre-fill password
        tagsText.value = a.tags.join(", ");
        form.timeout = a.timeout;
        form.verify_ssl = a.verify_ssl;
        form.is_active = a.is_active;
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

function close() {
    emit("update:open", false);
    setTimeout(() => {
        form.reset();
        tagsText.value = "";
        testResult.value = null;
        testMessage.value = null;
    }, 200);
}

function splitTags(): string[] {
    return tagsText.value
        .split(",")
        .map((t) => t.trim())
        .filter(Boolean);
}

/**
 * Build the configuration payload from the current form values.
 */
function buildPayload(): Record<
    string,
    string | number | boolean | null | string[]
> {
    const payload: Record<string, string | number | boolean | null | string[]> =
        {
            name: form.name,
            url: form.url,
            tags: splitTags(),
            username: form.username || null,
            timeout: form.timeout,
            verify_ssl: form.verify_ssl,
            is_active: form.is_active,
        };

    if (isEdit.value) {
        // A blank password on edit keeps the existing credential.
        if (form.password !== "") {
            payload.password = form.password;
        }
    } else {
        payload.password = form.password || null;
    }

    return payload;
}

/**
 * Read the Laravel XSRF-TOKEN cookie for the raw fetch used by the
 * creation-time connection check.
 */
function getXsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : "";
}

async function testConnection() {
    testProcessing.value = true;
    testResult.value = null;
    testMessage.value = null;

    try {
        if (isEdit.value && props.apprise) {
            // Edit: test the saved instance (credentials already stored).
            await new Promise<void>((resolve) => {
                router.post(
                    route(
                        "speedtest.integration.apprise.test",
                        { apprise: props.apprise!.id },
                        false,
                    ),
                    {},
                    {
                        preserveScroll: true,
                        onSuccess: () => {
                            testResult.value = "success";
                            testMessage.value =
                                "Test notification sent — the Apprise server accepted it.";
                            resolve();
                        },
                        onError: () => {
                            testResult.value = "failed";
                            testMessage.value =
                                "Test notification failed — check the URL, tags and credentials.";
                            resolve();
                        },
                        onFinish: () => resolve(),
                    },
                );
            });

            return;
        }

        // Creation: validate the current form values against the server
        // without saving anything.
        const res = await fetch(
            route("speedtest.integration.apprise.test-config", {}, false),
            {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-XSRF-TOKEN": getXsrfToken(),
                },
                body: JSON.stringify(buildPayload()),
            },
        );

        const data = (await res.json().catch(() => ({}))) as {
            success?: boolean;
            message?: string;
        };

        if (res.ok && data.success) {
            testResult.value = "success";
            testMessage.value =
                data.message ??
                "Connection successful — the Apprise server accepted the test notification.";
        } else {
            testResult.value = "failed";
            testMessage.value =
                data.message ??
                "Connection failed — check the URL, tags and credentials.";
        }
    } catch {
        testResult.value = "failed";
        testMessage.value = "Network error — try again.";
    } finally {
        testProcessing.value = false;
    }
}

function save() {
    const payload = buildPayload();

    const method = isEdit.value ? "patch" : "post";
    const routeName = isEdit.value
        ? "speedtest.integration.apprise.update"
        : "speedtest.integration.apprise.store";
    const routeParams = isEdit.value ? { apprise: props.apprise!.id } : {};

    router[method](route(routeName, routeParams, false), payload, {
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
                    {{ isEdit ? `Edit — ${apprise?.name}` : "Add Apprise" }}
                </DialogTitle>
            </DialogHeader>

            <div class="space-y-3">
                <!-- Name -->
                <div class="space-y-1.5">
                    <Label class="text-xs">Name</Label>
                    <Input
                        v-model="form.name"
                        placeholder="e.g. Production notifications"
                        class="text-xs"
                    />
                    <p v-if="form.errors.name" class="text-destructive text-xs">
                        {{ form.errors.name }}
                    </p>
                </div>

                <!-- URL -->
                <div class="space-y-1.5">
                    <Label class="text-xs">Apprise API URL</Label>
                    <Input
                        v-model="form.url"
                        placeholder="https://apprise.example.com/notify"
                        class="font-mono text-xs"
                    />
                    <p class="text-muted-foreground text-[10px]">
                        Apprise API endpoint — include <code>/notify</code> (or
                        <code>/notify/{key}</code>) for stateful servers.
                    </p>
                    <p v-if="form.errors.url" class="text-destructive text-xs">
                        {{ form.errors.url }}
                    </p>
                </div>

                <!-- Verify SSL -->
                <div class="flex items-center justify-between gap-2 pb-1">
                    <Label class="text-xs">Verify SSL</Label>
                    <Switch v-model:checked="form.verify_ssl" />
                </div>

                <!-- Tags -->
                <div class="space-y-1.5">
                    <Label class="text-xs">
                        Tags
                        <span class="text-muted-foreground font-normal"
                            >(optional, comma-separated)</span
                        >
                    </Label>
                    <Input
                        v-model="tagsText"
                        placeholder="production, critical"
                        class="font-mono text-xs"
                    />
                    <p class="text-muted-foreground text-[10px]">
                        Notifications are sent with these tags so the Apprise
                        server routes them to matching services. Leave empty to
                        notify all configured services.
                    </p>
                    <p v-if="form.errors.tags" class="text-destructive text-xs">
                        {{ form.errors.tags }}
                    </p>
                </div>

                <Separator />

                <!-- Basic Auth -->
                <div class="space-y-1.5">
                    <Label class="text-xs">
                        Basic Auth
                        <span class="text-muted-foreground font-normal"
                            >(optional)</span
                        >
                    </Label>
                    <div class="grid grid-cols-2 gap-2">
                        <Input
                            v-model="form.username"
                            placeholder="Username"
                            class="text-xs"
                        />
                        <Input
                            v-model="form.password"
                            type="password"
                            :placeholder="
                                isEdit && apprise?.has_credentials
                                    ? 'Leave blank to keep existing password'
                                    : 'Password'
                            "
                            class="text-xs"
                        />
                    </div>
                    <p class="text-muted-foreground text-[10px]">
                        Required when your Apprise server is protected by HTTP
                        Basic Auth. Passwords are stored encrypted.
                    </p>
                </div>

                <!-- Timeout -->
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

                <!-- Active (edit only — new instances are always active) -->
                <div
                    v-if="isEdit"
                    class="flex items-center justify-between gap-2 pb-1"
                >
                    <Label class="text-xs">Active</Label>
                    <Switch v-model:checked="form.is_active" />
                </div>

                <!-- Test result -->
                <div
                    v-if="testResult"
                    class="rounded-md border p-2 text-xs"
                    :class="
                        testResult === 'success'
                            ? 'border-green-600/20 bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-400'
                            : 'border-red-600/20 bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400'
                    "
                >
                    {{ testMessage }}
                </div>
            </div>

            <DialogFooter>
                <Button
                    variant="outline"
                    size="sm"
                    class="text-xs"
                    :disabled="testProcessing || form.processing"
                    @click="testConnection"
                >
                    <Loader2
                        v-if="testProcessing"
                        class="mr-1.5 h-3 w-3 animate-spin"
                    />
                    {{
                        testProcessing
                            ? "Testing…"
                            : isEdit
                              ? "Test"
                              : "Test connection"
                    }}
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    class="text-xs"
                    :disabled="testProcessing || form.processing"
                    @click="close"
                >
                    Cancel
                </Button>
                <Button
                    size="sm"
                    class="text-xs"
                    :disabled="testProcessing || form.processing"
                    @click="save"
                >
                    <Loader2
                        v-if="form.processing"
                        class="mr-1.5 h-3 w-3 animate-spin"
                    />
                    {{ isEdit ? "Save changes" : "Add Apprise" }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
