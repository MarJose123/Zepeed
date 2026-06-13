<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { Network, Plus } from "@lucide/vue";
import { ref } from "vue";
import PingTargetDeleteDialog from "@/components/network/PingTargetDeleteDialog.vue";
import PingTargetDialog from "@/components/network/PingTargetDialog.vue";
import PingTargetTable from "@/components/network/PingTargetTable.vue";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type { PingTarget } from "@/types/ping";

defineProps<{ targets: PingTarget[] }>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Network", href: "#" },
    {
        title: "Ping Targets",
        href: route("speedtest.network.ping-targets.index", {}, false),
    },
];

const showDialog = ref(false);
const editTarget = ref<PingTarget | null>(null);
const deleteTarget = ref<PingTarget | null>(null);

const openCreate = () => {
    editTarget.value = null;
    showDialog.value = true;
};
const openEdit = (t: PingTarget) => {
    editTarget.value = t;
    showDialog.value = true;
};
const openDelete = (t: PingTarget) => {
    deleteTarget.value = t;
};

const runNow = (t: PingTarget) => {
    router.post(
        route(
            "speedtest.network.ping-targets.run-now",
            { pingTarget: t.id },
            false,
        ),
        {},
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Ping Targets" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-bold tracking-tight">
                        Ping Targets
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Monitor network connectivity, latency, and packet loss
                        to hosts.
                    </p>
                </div>
                <Button size="sm" @click="openCreate">
                    <Plus class="mr-1.5 h-4 w-4" />
                    Add Target
                </Button>
            </div>

            <!-- Empty state -->
            <div
                v-if="targets.length === 0"
                class="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed py-16"
            >
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-muted"
                >
                    <Network class="h-5 w-5 text-muted-foreground" />
                </div>
                <p class="text-sm text-muted-foreground">
                    No ping targets configured yet.
                </p>
                <Button size="sm" variant="outline" @click="openCreate">
                    <Plus class="mr-1.5 h-4 w-4" />
                    Add your first target
                </Button>
            </div>

            <PingTargetTable
                v-else
                :targets="targets"
                @edit="openEdit"
                @delete="openDelete"
                @run-now="runNow"
            />
        </div>
    </AppLayout>

    <PingTargetDialog
        :open="showDialog"
        :target="editTarget"
        @close="showDialog = false"
    />

    <PingTargetDeleteDialog
        :target="deleteTarget"
        @close="deleteTarget = null"
    />
</template>
