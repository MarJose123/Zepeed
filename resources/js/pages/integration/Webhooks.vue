<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { Link2, Plus } from "lucide-vue-next";
import { ref } from "vue";
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
import { Button } from "@/components/ui/button";
import { Empty } from "@/components/ui/empty";
import WebhookCard from "@/components/webhook/WebhookCard.vue";
import WebhookDeliveryLog from "@/components/webhook/WebhookDeliveryLog.vue";
import WebhookFormDialog from "@/components/webhook/WebhookFormDialog.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type { Webhook, WebhookDelivery } from "@/types/webhook";

const props = defineProps<{
    webhooks: Webhook[];
    deliveries: WebhookDelivery[];
    selected_webhook_id: string | null;
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Integration", href: "#" },
    {
        title: "Webhooks",
        href: route("speedtest.integration.webhooks.index", {}, false),
    },
];

// ── Selected webhook for delivery log ────────────────────────────────────────
const selectedId = ref<string | null>(props.selected_webhook_id);

const selectedWebhook = () =>
    props.webhooks.find((w) => w.id === selectedId.value) ??
    props.webhooks[0] ??
    null;

// ── Add / Edit dialog ─────────────────────────────────────────────────────────
const showForm = ref(false);
const editWebhook = ref<Webhook | null>(null);

function openAdd() {
    editWebhook.value = null;
    showForm.value = true;
}

function openEdit(webhook: Webhook) {
    editWebhook.value = webhook;
    showForm.value = true;
}

// ── Delete dialog ─────────────────────────────────────────────────────────────
const showDelete = ref(false);
const deleteTarget = ref<Webhook | null>(null);

function confirmDelete(webhook: Webhook) {
    deleteTarget.value = webhook;
    showDelete.value = true;
}

function destroyWebhook() {
    if (!deleteTarget.value) return;
    router.delete(
        route(
            "speedtest.integration.webhooks.destroy",
            { webhook: deleteTarget.value.id },
            false,
        ),
        {
            preserveScroll: true,
            onSuccess: () => {
                showDelete.value = false;
                deleteTarget.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Webhooks" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <!-- Page header -->
            <div class="flex items-start justify-between gap-3 py-5">
                <div>
                    <h1 class="text-xl font-semibold">Webhooks</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Configure reusable endpoints triggered by alert rules
                    </p>
                </div>
            </div>

            <!-- Card grid -->
            <div
                v-if="webhooks.length > 0"
                class="grid grid-cols-1 gap-3 md:grid-cols-5"
            >
                <WebhookCard
                    v-for="webhook in webhooks"
                    :key="webhook.id"
                    :webhook="webhook"
                    :selected="selectedId === webhook.id"
                    @select="selectedId = webhook.id"
                    @edit="openEdit(webhook)"
                    @delete="confirmDelete(webhook)"
                />

                <!-- Add new card -->
                <div
                    class="border-border hover:border-border-secondary flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed py-10 transition-colors"
                    @click="openAdd"
                >
                    <div
                        class="bg-muted flex h-9 w-9 items-center justify-center rounded-md"
                    >
                        <Plus class="text-muted-foreground h-4 w-4" />
                    </div>
                    <span class="text-muted-foreground text-xs"
                        >Add webhook</span
                    >
                </div>
            </div>

            <!-- Empty state -->
            <Empty v-else>
                <Link2 class="text-muted-foreground h-6 w-6" />
                <div>
                    <p class="text-muted-foreground text-sm">
                        No webhooks configured.
                    </p>
                    <p class="text-muted-foreground mt-0.5 text-xs">
                        Add a webhook to use it as an action in alert rules.
                    </p>
                </div>
                <Button
                    size="sm"
                    variant="outline"
                    class="mt-1"
                    @click="openAdd"
                >
                    <Plus class="mr-1.5 h-4 w-4" />
                    Add webhook
                </Button>
            </Empty>

            <!-- Delivery log -->
            <WebhookDeliveryLog
                v-if="selectedWebhook()"
                :webhook="selectedWebhook()!"
                :deliveries="deliveries"
            />

            <!-- Footer note -->
            <p class="text-muted-foreground pb-2 text-center text-xs">
                Webhooks are dispatched w/o signed payloads and automatic retry
                on failure.
            </p>
        </div>
    </AppLayout>

    <!-- Add / Edit dialog -->
    <WebhookFormDialog v-model:open="showForm" :webhook="editWebhook" />

    <!-- Delete confirm dialog -->
    <AlertDialog v-model:open="showDelete">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Remove webhook?</AlertDialogTitle>
                <AlertDialogDescription as="div">
                    <!-- In-use warning -->
                    <div
                        v-if="deleteTarget?.is_used_in_rules"
                        class="mb-3 flex items-start gap-2 rounded-md border border-amber-300 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950"
                    >
                        <span class="mt-0.5 text-amber-600">⚠</span>
                        <div>
                            <p
                                class="text-xs font-medium text-amber-700 dark:text-amber-400"
                            >
                                Webhook is in use
                            </p>
                            <p
                                class="text-xs text-amber-600 dark:text-amber-500"
                            >
                                <strong>{{ deleteTarget.name }}</strong> is used
                                by
                                {{
                                    deleteTarget.used_in_rule_names.join(", ")
                                }}. Removing it will disable those actions.
                            </p>
                        </div>
                    </div>

                    <!-- Safe to delete -->
                    <p
                        v-else-if="deleteTarget"
                        class="text-muted-foreground mb-2"
                    >
                        {{ deleteTarget.name }} is not used in any alert rules
                        and can be safely removed.
                    </p>

                    <p class="text-muted-foreground">
                        This action cannot be undone. The webhook and all its
                        delivery logs will be permanently removed.
                    </p>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Keep webhook</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-destructive text-white hover:bg-destructive/80"
                    @click="destroyWebhook"
                >
                    {{
                        deleteTarget?.is_used_in_rules
                            ? "Remove anyway"
                            : "Remove"
                    }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
