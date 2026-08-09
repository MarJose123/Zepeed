<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { Bell, Plus } from "@lucide/vue";
import { ref } from "vue";
import AppriseCard from "@/components/apprise/AppriseCard.vue";
import AppriseFormDialog from "@/components/apprise/AppriseFormDialog.vue";
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
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type { Apprise } from "@/types/apprise";

const props = defineProps<{
    apprises: Apprise[];
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Integration", href: "#" },
    {
        title: "Apprise",
        href: route("speedtest.integration.apprise.index", {}, false),
    },
];

// ── Selected card ────────────────────────────────────────────────────────────
const selectedId = ref<string | null>(props.apprises[0]?.id ?? null);

// ── Add / Edit dialog ─────────────────────────────────────────────────────────
const showForm = ref(false);
const editApprise = ref<Apprise | null>(null);

function openAdd() {
    editApprise.value = null;
    showForm.value = true;
}

function openEdit(apprise: Apprise) {
    editApprise.value = apprise;
    showForm.value = true;
}

// ── Delete dialog ─────────────────────────────────────────────────────────────
const showDelete = ref(false);
const deleteTarget = ref<Apprise | null>(null);

function confirmDelete(apprise: Apprise) {
    deleteTarget.value = apprise;
    showDelete.value = true;
}

function destroyApprise() {
    if (!deleteTarget.value) {
        return;
    }

    router.delete(
        route(
            "speedtest.integration.apprise.destroy",
            { apprise: deleteTarget.value.id },
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
    <Head title="Apprise" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <!-- Page header -->
            <div class="flex items-start justify-between gap-3 py-5">
                <div>
                    <h1 class="text-xl font-semibold">Apprise</h1>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Apprise notification gateways triggered by alert rules
                    </p>
                </div>
            </div>

            <!-- Card grid -->
            <div
                v-if="apprises.length > 0"
                class="grid grid-cols-1 gap-3 md:grid-cols-5"
            >
                <AppriseCard
                    v-for="apprise in apprises"
                    :key="apprise.id"
                    :apprise="apprise"
                    :selected="selectedId === apprise.id"
                    @select="selectedId = apprise.id"
                    @edit="openEdit(apprise)"
                    @delete="confirmDelete(apprise)"
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
                        >Add Apprise</span
                    >
                </div>
            </div>

            <!-- Empty state -->
            <Empty v-else>
                <Bell class="text-muted-foreground h-6 w-6" />
                <div>
                    <p class="text-muted-foreground text-sm">
                        No Apprise instances configured.
                    </p>
                    <p class="text-muted-foreground mt-0.5 text-xs">
                        Add an Apprise API server to use it as an action in
                        alert rules.
                    </p>
                </div>
                <Button
                    size="sm"
                    variant="outline"
                    class="mt-1"
                    @click="openAdd"
                >
                    <Plus class="mr-1.5 h-4 w-4" />
                    Add Apprise
                </Button>
            </Empty>

            <!-- Footer note -->
            <p class="text-muted-foreground pb-2 text-center text-xs">
                Notifications are sent to the Apprise API server with your
                configured tags and optional Basic Auth credentials.
            </p>
        </div>
    </AppLayout>

    <!-- Add / Edit dialog -->
    <AppriseFormDialog v-model:open="showForm" :apprise="editApprise" />

    <!-- Delete confirm dialog -->
    <AlertDialog v-model:open="showDelete">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Remove Apprise?</AlertDialogTitle>
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
                                Apprise instance is in use
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
                        This action cannot be undone. The Apprise instance and
                        its stored credentials will be permanently removed.
                    </p>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Keep Apprise</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-destructive text-white hover:bg-destructive/80"
                    @click="destroyApprise"
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
