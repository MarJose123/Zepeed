<script setup lang="ts">
import { ChevronDown, Clock } from "@lucide/vue";
import { ref } from "vue";
import AddScheduleModal from "@/components/speedtest/AddScheduleModal.vue";
import ProviderScheduleItem from "@/components/speedtest/ProviderScheduleItem.vue";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import type { ProviderWithSchedules } from "@/types/provider";

const props = defineProps<{
    provider: ProviderWithSchedules;
    defaultOpen?: boolean;
}>();

const isOpen = ref(props.defaultOpen ?? false);
const showAddModal = ref(false);

const faviconError = ref(false);

const onFaviconError = () => {
    faviconError.value = true;
};

const toggleProvider = () => {
    isOpen.value = !isOpen.value;
};
</script>

<template>
    <div>
        <Separator />

        <!-- Provider header -->
        <div
            class="flex cursor-pointer items-center gap-3 px-4 py-3.5 transition-colors hover:bg-muted/50"
            :class="{ 'hover:bg-background': isOpen }"
            @click="toggleProvider"
        >
            <!-- Favicon with Clock lucide fallback -->
            <div
                class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-md"
            >
                <img
                    v-if="!faviconError"
                    :src="`https://www.google.com/s2/favicons?domain=${provider.website_link}&sz=32`"
                    :alt="provider.label"
                    class="size-7 object-cover"
                    @error="onFaviconError"
                />
                <Clock v-else class="text-muted-foreground h-4 w-4" />
            </div>

            <!-- Provider info -->
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium">
                        {{ provider.label }}
                    </span>
                    <Badge
                        class="text-[10px]"
                        :class="
                            provider.is_enabled
                                ? 'border-green-600/20 bg-green-50 text-green-700 dark:border-green-400/20 dark:bg-green-950 dark:text-green-400'
                                : 'border-border bg-muted text-muted-foreground'
                        "
                        variant="outline"
                    >
                        {{ provider.is_enabled ? "enabled" : "disabled" }}
                    </Badge>
                </div>
                <p class="text-muted-foreground text-xs">
                    <template v-if="provider.schedule_count === 1">
                        1 schedule configured
                    </template>
                    <template v-else-if="provider.schedule_count > 1">
                        {{ provider.schedule_count }} schedules configured
                    </template>
                    <template v-else>No schedules configured</template>
                </p>
            </div>

            <!-- Chevron -->
            <ChevronDown
                class="text-muted-foreground h-4 w-4 shrink-0 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
            />
        </div>

        <!-- Expanded content -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-96"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 max-h-96"
            leave-to-class="opacity-0 max-h-0"
        >
            <div v-if="isOpen" class="overflow-hidden px-4 pb-4 pl-[52px]">
                <!-- Schedules list -->
                <div class="mb-4 space-y-2">
                    <ProviderScheduleItem
                        v-for="schedule in provider.schedules"
                        :key="schedule.id"
                        :schedule="schedule"
                        :provider="provider"
                    />
                </div>

                <!-- Add schedule button -->
                <button
                    class="mt-2 w-full rounded-md border border-dashed border-border bg-transparent py-2 text-sm font-medium text-muted-foreground transition-colors hover:border-muted-foreground/50 hover:text-foreground"
                    @click.stop="showAddModal = true"
                >
                    <i class="ti ti-plus mr-1.5" style="font-size: 14px"></i>Add
                    schedule
                </button>
            </div>
        </Transition>

        <!-- Add schedule modal -->
        <AddScheduleModal
            v-if="showAddModal"
            :provider="provider"
            @close="showAddModal = false"
        />
    </div>
</template>
