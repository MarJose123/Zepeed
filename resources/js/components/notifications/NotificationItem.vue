<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { Download, X, CheckCircle2, XCircle } from "@lucide/vue";
import { computed } from "vue";
import { Button } from "@/components/ui/button";
import type {
    ExportCompletedData,
    ExportFailedData,
    TUserNotification,
} from "@/types/notification";

const props = defineProps<{ notification: TUserNotification }>();

const isCompleted = computed(
    () => props.notification.type === "ExportCompletedNotification",
);
const isUnread = computed(() => props.notification.read_at === null);

const completedData = computed(() =>
    isCompleted.value ? (props.notification.data as ExportCompletedData) : null,
);

const failedData = computed(() =>
    !isCompleted.value ? (props.notification.data as ExportFailedData) : null,
);

function dismiss(): void {
    router.delete(
        route("notifications.dismiss", { notification: props.notification.id }),
        {
            preserveScroll: true,
            only: ["notifications"],
        },
    );
}

function download(): void {
    if (completedData.value?.download_url) {
        window.open(completedData.value.download_url, "_blank");
    }
}
</script>

<template>
    <div
        class="flex items-start gap-3 rounded-lg p-3 transition-colors"
        :class="isUnread ? 'bg-primary/5' : 'hover:bg-muted/50'"
    >
        <!-- Icon -->
        <div
            class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full"
            :class="
                isCompleted
                    ? 'bg-primary/10 text-primary'
                    : 'bg-destructive/10 text-destructive'
            "
        >
            <CheckCircle2 v-if="isCompleted" class="size-3.5" />
            <XCircle v-else class="size-3.5" />
        </div>

        <!-- Content -->
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1.5">
                <span class="text-sm font-medium">
                    {{
                        isCompleted
                            ? `${completedData!.module_label} ready`
                            : `${failedData!.module_label} failed`
                    }}
                </span>
                <span
                    v-if="isUnread"
                    class="size-1.5 shrink-0 rounded-full bg-primary"
                />
            </div>

            <p
                v-if="isCompleted"
                class="mt-0.5 text-[11px] text-muted-foreground"
            >
                {{ completedData!.row_count ?? 0 }} rows ·
                {{ completedData!.format.toUpperCase() }}
                <span v-if="completedData!.expires_at">
                    · expires
                    {{
                        new Date(completedData!.expires_at).toLocaleDateString()
                    }}</span
                >
            </p>

            <p v-else class="mt-0.5 text-[11px] text-muted-foreground">
                {{
                    failedData!.failure_message ??
                    "Export could not be completed."
                }}
            </p>

            <p class="mt-1 text-[11px] text-muted-foreground">
                {{ new Date(notification.created_at).toLocaleString() }}
            </p>

            <Button
                v-if="isCompleted"
                variant="outline"
                size="sm"
                class="mt-2 h-7 gap-1.5 text-xs"
                @click="download"
            >
                <Download class="size-3" />
                Download {{ completedData!.format.toUpperCase() }}
            </Button>
        </div>

        <!-- Dismiss -->
        <button
            class="mt-0.5 shrink-0 rounded p-0.5 text-muted-foreground opacity-0 transition-opacity hover:text-foreground group-hover:opacity-100"
            aria-label="Dismiss"
            @click="dismiss"
        >
            <X class="size-3.5" />
        </button>
    </div>
</template>
