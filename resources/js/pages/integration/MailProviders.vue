<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { Plus } from "lucide-vue-next";
import { ref } from "vue";
import { watch } from "vue";
import AddProviderWizard from "@/components/mail/AddProviderWizard.vue";
import ProviderRow from "@/components/mail/ProviderRow.vue";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Empty } from "@/components/ui/empty";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type { MailProvider } from "@/types/mail";

const props = defineProps<{
    providers: MailProvider[];
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Settings", href: "#" },
    {
        title: "Mailer Integration",
        href: route("speedtest.integration.smtp.index", {}, false),
    },
];

// ── Add wizard ────────────────────────────────────────────────────────────────
const showWizard = ref(false);

// ── Drag and drop reorder ─────────────────────────────────────────────────────
const localProviders = ref<MailProvider[]>([...props.providers]);
const draggingId = ref<string | null>(null);
const dragOverId = ref<string | null>(null);
const orderChanged = ref(false);

// Keep local list in sync when Inertia refreshes props
watch(
    () => props.providers,
    (val) => {
        localProviders.value = [...val];
        orderChanged.value = false;
    },
    { deep: true },
);

function onDragStart(id: string) {
    draggingId.value = id;
}

function onDragOver(id: string) {
    if (draggingId.value === null || draggingId.value === id) return;
    dragOverId.value = id;

    // Reorder locally for live preview
    const from = localProviders.value.findIndex(
        (p) => p.id === draggingId.value,
    );
    const to = localProviders.value.findIndex((p) => p.id === id);
    if (from === -1 || to === -1) return;

    const reordered = [...localProviders.value];
    const [moved] = reordered.splice(from, 1);
    reordered.splice(to, 0, moved);

    // Update priority numbers and primary flag
    localProviders.value = reordered.map((p, i) => ({
        ...p,
        priority: i + 1,
        is_primary: i === 0,
    }));

    orderChanged.value = true;
}

function onDragLeave() {
    dragOverId.value = null;
}

function onDrop() {
    dragOverId.value = null;
}

function onDragEnd() {
    draggingId.value = null;
    dragOverId.value = null;
}

function saveOrder() {
    router.post(
        route("settings.mail.reorder", {}, false),
        { ordered_ids: localProviders.value.map((p) => p.id) },
        {
            preserveScroll: true,
            onSuccess: () => {
                orderChanged.value = false;
            },
        },
    );
}

function discardOrder() {
    localProviders.value = [...props.providers];
    orderChanged.value = false;
}
</script>

<template>
    <Head title="Mailer Integration" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex flex-col gap-1 py-5">
                <h1 class="text-xl font-semibold">Mailer integration</h1>
                <p class="text-muted-foreground text-sm">
                    Configure mail providers for alert notifications.
                </p>
            </div>

            <!-- Provider list card -->
            <Card class="overflow-hidden p-0">
                <CardHeader class="border-border border-b px-4 py-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-sm font-medium"
                                >Configured providers</CardTitle
                            >
                            <CardDescription class="text-xs">
                                Drag rows to reorder — provider 1 is primary,
                                rest are fallbacks
                            </CardDescription>
                        </div>
                        <Button
                            v-if="localProviders.length > 0"
                            size="sm"
                            variant="outline"
                            @click="showWizard = true"
                        >
                            <Plus class="mr-1.5 h-4 w-4" />
                            Add provider
                        </Button>
                    </div>
                </CardHeader>

                <CardContent class="p-4">
                    <!-- Empty state -->
                    <Empty v-if="localProviders.length === 0">
                        <p class="text-muted-foreground text-sm">
                            No mail providers configured.
                        </p>
                        <p class="text-muted-foreground text-xs">
                            Add a provider to enable alert email notifications.
                        </p>
                        <Button
                            size="sm"
                            variant="outline"
                            class="mt-2"
                            @click="showWizard = true"
                        >
                            <Plus class="mr-1.5 h-4 w-4" />
                            Add provider
                        </Button>
                    </Empty>

                    <!-- Provider rows -->
                    <div v-else class="flex flex-col gap-2">
                        <ProviderRow
                            v-for="provider in localProviders"
                            :key="provider.id"
                            :provider="provider"
                            :is-dragging="draggingId === provider.id"
                            :is-drag-over="dragOverId === provider.id"
                            @dragstart="onDragStart(provider.id)"
                            @dragover="onDragOver(provider.id)"
                            @dragleave="onDragLeave"
                            @drop="onDrop"
                            @dragend="onDragEnd"
                        />
                    </div>
                </CardContent>
            </Card>

            <!-- Save order bar -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-2"
            >
                <div
                    v-if="orderChanged"
                    class="border-primary/30 bg-primary/5 flex items-center justify-between rounded-lg border px-4 py-3"
                >
                    <p class="text-primary text-xs">
                        Order changed — save to apply the new fallback chain.
                    </p>
                    <div class="flex items-center gap-2">
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="discardOrder"
                        >
                            Discard
                        </Button>
                        <Button size="sm" @click="saveOrder">
                            Save order
                        </Button>
                    </div>
                </div>
            </Transition>

            <!-- Info note -->
            <p class="text-muted-foreground text-center text-xs">
                If provider 1 fails, provider 2 is tried automatically, and so
                on.
            </p>
        </div>
    </AppLayout>

    <!-- Add provider wizard -->
    <AddProviderWizard v-model:open="showWizard" />
</template>
