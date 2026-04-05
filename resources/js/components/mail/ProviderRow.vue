<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import { GripVertical, Loader2, Pencil, Trash2 } from "lucide-vue-next";
import { ref } from "vue";
import DriverConfigForm from "@/components/mail/DriverConfigForm.vue";
import { Badge } from "@/components/ui/badge";
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
import type { MailProvider } from "@/types/mail";

const props = defineProps<{
    provider: MailProvider;
    isDragging: boolean;
    isDragOver: boolean;
}>();

const emit = defineEmits<{
    dragstart: [e: DragEvent];
    dragover: [e: DragEvent];
    dragleave: [e: DragEvent];
    drop: [e: DragEvent];
    dragend: [];
}>();

// ── Status badge ──────────────────────────────────────────────────────────────
const statusVariant = (p: MailProvider) => {
    if (p.failure_count > 3) {
        return "destructive";
    }

    if (p.last_used_at) {
        return "default";
    }

    return "secondary";
};

const statusLabel = (p: MailProvider) => {
    if (p.failure_count > 3) {
        return "failed";
    }

    if (p.last_used_at) {
        return "connected";
    }

    return "not tested";
};

const lastUsedLabel = (p: MailProvider) => {
    if (!p.last_used_at) {
        return "Never";
    }

    const diff = Date.now() - new Date(p.last_used_at).getTime();
    const mins = Math.floor(diff / 60_000);

    if (mins < 60) {
        return `${mins} min ago`;
    }

    const hrs = Math.floor(mins / 60);

    if (hrs < 24) {
        return `${hrs}h ago`;
    }

    return `${Math.floor(hrs / 24)}d ago`;
};

// ── Test ──────────────────────────────────────────────────────────────────────
const showTest = ref(false);
const testEmail = ref("");
const testProcessing = ref(false);

function sendTest() {
    testProcessing.value = true;
    router.post(
        route(
            "speedtest.integration.smtp.test",
            { mailProvider: props.provider.id },
            false,
        ),
        { email: testEmail.value },
        {
            preserveScroll: true,
            onFinish: () => {
                testProcessing.value = false;
                showTest.value = false;
            },
        },
    );
}

// ── Edit ──────────────────────────────────────────────────────────────────────
const showEdit = ref(false);

const editForm = useForm({
    label: props.provider.label,
    from_address: props.provider.from_address,
    from_name: props.provider.from_name,
    config: {} as Record<string, string>,
});

function saveEdit() {
    editForm.patch(
        route(
            "speedtest.integration.smtp.update",
            { mailProvider: props.provider.id },
            false,
        ),
        {
            preserveScroll: true,
            onSuccess: () => {
                showEdit.value = false;
            },
        },
    );
}

// ── Delete ────────────────────────────────────────────────────────────────────
function destroy() {
    router.delete(
        route(
            "speedtest.integration.smtp.destroy",
            { mailProvider: props.provider.id },
            false,
        ),
        { preserveScroll: true },
    );
}

const driverIcons: Record<string, string> = {
    smtp: "📧",
    resend: "✉️",
    mailgun: "📮",
    postmark: "📬",
    ses: "☁️",
    sendmail: "📨",
};
</script>

<template>
    <div
        class="flex items-center gap-3 rounded-lg border p-3.5 transition-all"
        :class="{
            'border-primary/50 bg-primary/5': provider.is_primary,
            'border-border': !provider.is_primary && !isDragOver,
            'border-primary bg-primary/5 opacity-80': isDragOver,
            'opacity-40': isDragging,
        }"
        draggable="true"
        @dragstart="emit('dragstart', $event)"
        @dragover.prevent="emit('dragover', $event)"
        @dragleave="emit('dragleave', $event)"
        @drop.prevent="emit('drop', $event)"
        @dragend="emit('dragend')"
    >
        <!-- Drag handle -->
        <div class="text-muted-foreground cursor-grab active:cursor-grabbing">
            <GripVertical class="h-4 w-4" />
        </div>

        <!-- Priority badge -->
        <div
            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-medium"
            :class="
                provider.is_primary
                    ? 'bg-primary/10 text-primary'
                    : 'bg-muted text-muted-foreground'
            "
        >
            {{ provider.priority }}
        </div>

        <!-- Provider icon -->
        <div
            class="bg-muted border-border flex h-7 w-7 shrink-0 items-center justify-center rounded-md border text-sm"
        >
            {{ driverIcons[provider.driver] }}
        </div>

        <!-- Info -->
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-sm font-medium">{{ provider.label }}</span>
                <Badge
                    class="text-[10px]"
                    :class="
                        provider.is_primary
                            ? 'border-primary/30 bg-primary/10 text-primary'
                            : 'border-border bg-muted text-muted-foreground'
                    "
                    variant="outline"
                >
                    {{ provider.is_primary ? "primary" : "fallback" }}
                </Badge>
                <Badge :variant="statusVariant(provider)" class="text-[10px]">
                    {{ statusLabel(provider) }}
                </Badge>
            </div>
            <div
                class="text-muted-foreground mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[10px]"
            >
                <span>Last sent: {{ lastUsedLabel(provider) }}</span>
                <span>Failures: {{ provider.failure_count }}</span>
                <span>{{ provider.config_summary }}</span>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex shrink-0 items-center gap-1.5">
            <Button
                variant="outline"
                size="sm"
                class="h-7 text-xs"
                @click="showTest = true"
            >
                Test
            </Button>
            <Button
                variant="outline"
                size="sm"
                class="h-7 w-7 p-0"
                @click="showEdit = true"
            >
                <Pencil class="h-3 w-3" />
            </Button>
            <Button
                variant="ghost"
                size="sm"
                class="text-muted-foreground hover:text-destructive h-7 w-7 p-0"
                @click="destroy"
            >
                <Trash2 class="h-3 w-3" />
            </Button>
        </div>
    </div>

    <!-- Test dialog -->
    <Dialog v-model:open="showTest">
        <DialogContent class="max-w-sm">
            <DialogHeader>
                <DialogTitle class="text-sm"
                    >Test — {{ provider.label }}</DialogTitle
                >
            </DialogHeader>
            <div class="space-y-1.5 py-2">
                <Label class="text-xs">Send test email to</Label>
                <Input
                    v-model="testEmail"
                    type="email"
                    placeholder="admin@mail.com"
                    class="text-xs"
                />
                <p class="text-muted-foreground text-[10px]">
                    A test email will be sent using this provider's
                    configuration.
                </p>
            </div>
            <DialogFooter>
                <Button variant="secondary" size="sm" @click="showTest = false"
                    >Cancel</Button
                >
                <Button
                    size="sm"
                    :disabled="!testEmail || testProcessing"
                    @click="sendTest"
                >
                    <Loader2
                        v-if="testProcessing"
                        class="mr-1.5 h-3 w-3 animate-spin"
                    />
                    Send test
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Edit dialog -->
    <Dialog v-model:open="showEdit">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle class="text-sm"
                    >Edit — {{ provider.label }}</DialogTitle
                >
            </DialogHeader>
            <div class="space-y-3 py-2">
                <div class="space-y-1.5">
                    <Label class="text-xs">Label</Label>
                    <Input v-model="editForm.label" class="text-xs" />
                    <p
                        v-if="editForm.errors.label"
                        class="text-destructive text-xs"
                    >
                        {{ editForm.errors.label }}
                    </p>
                </div>
                <Separator />
                <DriverConfigForm
                    :driver="provider.driver"
                    :config="editForm.config"
                    @update:config="editForm.config = $event"
                />
                <Separator />
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <Label class="text-xs">From address</Label>
                        <Input
                            v-model="editForm.from_address"
                            class="text-xs"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs">From name</Label>
                        <Input v-model="editForm.from_name" class="text-xs" />
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button variant="secondary" size="sm" @click="showEdit = false"
                    >Cancel</Button
                >
                <Button
                    size="sm"
                    :disabled="editForm.processing"
                    @click="saveEdit"
                >
                    <Loader2
                        v-if="editForm.processing"
                        class="mr-1.5 h-3 w-3 animate-spin"
                    />
                    Save changes
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
