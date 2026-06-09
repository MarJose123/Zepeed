<script setup lang="ts">
import { AlertTriangle, CalendarClock } from "@lucide/vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import type { ProviderSchedule } from "@/types/provider";

const props = defineProps<{
    open: boolean;
    providerName: string;
    schedules: ProviderSchedule[];
}>();

const emit = defineEmits<{
    (e: "confirm"): void;
    (e: "cancel"): void;
}>();
</script>

<template>
    <Dialog :open="props.open">
        <DialogContent
            class="max-w-md"
            @pointer-down-outside.prevent
            @escape-key-down.prevent
        >
            <DialogHeader>
                <div class="flex items-center gap-3 mb-1">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-950"
                    >
                        <AlertTriangle
                            class="h-4 w-4 text-amber-600 dark:text-amber-400"
                        />
                    </div>
                    <DialogTitle class="text-base font-bold">
                        Disable {{ providerName }}?
                    </DialogTitle>
                </div>
                <DialogDescription class="text-sm text-muted-foreground">
                    Disabling this provider will prevent all associated
                    schedules from running.
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="schedules.length"
                class="rounded-lg border border-border bg-muted/40 divide-y divide-border"
            >
                <div
                    v-for="schedule in schedules"
                    :key="schedule.id"
                    class="flex items-center gap-3 px-3 py-2.5"
                >
                    <CalendarClock
                        class="h-3.5 w-3.5 shrink-0 text-muted-foreground"
                    />
                    <div class="flex flex-col gap-0.5 min-w-0">
                        <span class="text-sm truncate">{{
                            schedule.label
                        }}</span>
                        <span class="text-[11px] text-muted-foreground">
                            {{ schedule.cron_expression ?? "—" }}
                        </span>
                    </div>
                </div>
            </div>

            <p v-else class="text-sm text-muted-foreground">
                No active schedules are currently assigned to this provider.
            </p>

            <DialogFooter class="gap-2">
                <Button variant="outline" size="sm" @click="emit('cancel')">
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    size="sm"
                    @click="emit('confirm')"
                >
                    Disable provider
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
