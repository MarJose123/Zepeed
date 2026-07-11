<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { Bell, CheckCheck } from "@lucide/vue";
import { watch } from "vue";
import NotificationItem from "@/components/notifications/NotificationItem.vue";
import { Button } from "@/components/ui/button";
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from "@/components/ui/sheet";
import { useNotificationSheet } from "@/composables/useNotificationSheet";
import type { TUserNotification } from "@/types/notification";

defineProps<{ notifications: TUserNotification[]; unreadCount: number }>();

const { open } = useNotificationSheet();

// Auto-mark all as read when the sheet opens
watch(open, (isOpen) => {
    if (isOpen) {
        // Lazy-load notifications for the sheet
        router.reload({ only: ["notifications"] });
    }
});

function markAllRead(): void {
    router.post(
        route("notifications.read-all"),
        {},
        {
            preserveScroll: true,
            only: ["auth"],
        },
    );
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent
            side="right"
            class="flex w-full flex-col gap-0 p-0 sm:max-w-sm"
        >
            <SheetHeader class="flex flex-row items-center border-b px-4 py-3">
                <div class="flex w-full flex-col gap-2">
                    <SheetTitle class="text-sm font-bold">
                        Notifications
                    </SheetTitle>
                    <div class="flex flex-row justify-between items-center">
                        <SheetDescription
                            class="text-[11px] text-muted-foreground"
                        >
                            Notifications are kept until dismissed.
                        </SheetDescription>
                        <Button
                            v-if="unreadCount > 0"
                            variant="ghost"
                            size="sm"
                            class="h-7 gap-1.5 text-xs text-muted-foreground"
                            @click="markAllRead"
                        >
                            <CheckCheck class="size-3.5" />
                            Mark all read
                        </Button>
                    </div>
                </div>
            </SheetHeader>

            <div class="flex-1 overflow-y-auto">
                <div
                    v-if="notifications.length === 0"
                    class="flex flex-col items-center justify-center gap-2 py-16 text-center"
                >
                    <Bell class="size-8 text-muted-foreground/40" />
                    <p class="text-sm font-medium">No notifications</p>
                    <p class="text-[11px] text-muted-foreground">
                        Exports and alerts will appear here.
                    </p>
                </div>

                <div v-else class="group flex flex-col divide-y divide-border">
                    <NotificationItem
                        v-for="n in notifications"
                        :key="n.id"
                        :notification="n"
                    />
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
