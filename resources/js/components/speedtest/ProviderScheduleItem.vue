<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import cronstrue from "cronstrue";
import { Trash2, Edit2 } from "lucide-vue-next";
import { computed, ref } from "vue";
import EditScheduleModal from "@/components/speedtest/EditScheduleModal.vue";
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import type { ProviderSchedule, ProviderWithSchedules } from "@/types/provider";

const props = defineProps<{
    schedule: ProviderSchedule;
    provider: ProviderWithSchedules;
}>();

const showEditModal = ref(false);
const showDeleteConfirm = ref(false);
const isDeleting = ref(false);

const previewLabel = computed(() => {
    if (!props.schedule.cron_expression) {
        return null;
    }

    try {
        return cronstrue.toString(props.schedule.cron_expression, {
            throwExceptionOnParseError: true,
            verbose: false,
        });
    } catch {
        return null;
    }
});

const handleDelete = () => {
    isDeleting.value = true;
    router.delete(
        route(
            "speedtest.schedules.destroy",
            { providerSchedule: props.schedule.id },
            false,
        ),
        {
            preserveScroll: true,
            onFinish: () => {
                isDeleting.value = false;
                showDeleteConfirm.value = false;
            },
        },
    );
};
</script>

<template>
    <div
        class="flex items-center justify-between gap-3 rounded-md bg-muted/30 px-3 py-2.5"
    >
        <div class="min-w-0 flex-1">
            <div class="mb-1 flex items-center gap-2">
                <p class="text-sm font-medium text-foreground">
                    {{ schedule.label }}
                </p>
                <Badge
                    variant="outline"
                    class="rounded-full text-[10px]"
                    :class="
                        schedule.is_enabled
                            ? 'border-green-600/30 bg-green-50 text-green-700 dark:border-green-400/20 dark:bg-green-950 dark:text-green-400'
                            : 'border-border bg-transparent text-muted-foreground'
                    "
                >
                    {{ schedule.is_enabled ? "Enabled" : "Disabled" }}
                </Badge>
            </div>
            <p class="text-muted-foreground text-xs">
                <template v-if="schedule.cron_expression && previewLabel">
                    {{ previewLabel }}
                </template>
                <template v-else-if="schedule.cron_expression">
                    <code class="font-mono">{{
                        schedule.cron_expression
                    }}</code>
                </template>
                <template v-else>No schedule set</template>
                <span
                    v-if="schedule.next_run_at && schedule.is_enabled"
                    class="ml-1"
                >
                    · Next run {{ schedule.next_run_at }}
                </span>
            </p>
        </div>

        <!-- Action buttons -->
        <div class="flex shrink-0 gap-1.5">
            <Button
                size="sm"
                variant="ghost"
                class="h-8 w-8 p-0"
                @click="showEditModal = true"
            >
                <Edit2 class="h-4 w-4" />
                <span class="sr-only">Edit schedule</span>
            </Button>
            <Button
                size="sm"
                variant="ghost"
                class="h-8 w-8 p-0 text-destructive hover:bg-destructive/10 hover:text-destructive"
                @click="showDeleteConfirm = true"
            >
                <Trash2 class="h-4 w-4" />
                <span class="sr-only">Delete schedule</span>
            </Button>
        </div>

        <!-- Edit modal -->
        <EditScheduleModal
            v-if="showEditModal"
            :schedule="schedule"
            :provider="provider"
            @close="showEditModal = false"
        />

        <!-- Delete confirmation dialog -->
        <AlertDialog
            :open="showDeleteConfirm"
            @update:open="showDeleteConfirm = $event"
        >
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete schedule?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This will permanently remove the schedule
                        <span class="font-medium">{{ schedule.label }}</span>
                        for {{ provider.label }}. This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div class="flex justify-end gap-3">
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <Button
                        variant="destructive"
                        size="sm"
                        :disabled="isDeleting"
                        @click="handleDelete"
                    >
                        {{ isDeleting ? "Deleting..." : "Delete" }}
                    </Button>
                </div>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
