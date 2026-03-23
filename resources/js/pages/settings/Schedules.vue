<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";
import MaintenanceTab from "@/components/speedtest/MaintenanceTab.vue";
import ProviderScheduleRow from "@/components/speedtest/ProviderScheduleRow.vue";
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type {
    MaintenanceWindow,
    MaintenanceWindowStats,
    Provider,
    ProviderSchedule,
} from "@/types/provider";

defineProps<{
    providers: Provider[];
    schedules: ProviderSchedule[];
    windows: MaintenanceWindow[];
    stats: MaintenanceWindowStats;
    globalPause: boolean;
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Settings", href: "#" },
    { title: "Schedules", href: route("speedtest.schedules.index", {}, false) },
];

const activeTab = ref<"schedules" | "maintenance">("schedules");
</script>

<template>
    <Head title="Schedules" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex flex-col gap-1 py-5">
                <h1 class="text-xl font-semibold">Schedules</h1>
                <p class="text-muted-foreground text-sm">
                    Control when each provider runs and manage maintenance
                    windows
                </p>
            </div>

            <Tabs v-model="activeTab">
                <TabsList>
                    <TabsTrigger value="schedules">Schedules</TabsTrigger>
                    <TabsTrigger value="maintenance">
                        Maintenance
                        <span
                            v-if="globalPause"
                            class="bg-destructive ml-1.5 inline-block h-1.5 w-1.5 rounded-full"
                        />
                    </TabsTrigger>
                </TabsList>

                <!-- ── Schedules tab ── -->
                <TabsContent value="schedules" class="mt-4">
                    <Card class="overflow-hidden">
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-sm font-medium">
                                        Provider schedules
                                    </CardTitle>
                                    <CardDescription class="text-xs">
                                        Click a provider row to edit its
                                        schedule
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>

                        <ProviderScheduleRow
                            v-for="(schedule, index) in schedules"
                            :key="schedule.provider_slug"
                            :schedule="schedule"
                            :default-open="index === 0"
                        />
                    </Card>
                </TabsContent>

                <!-- ── Maintenance tab ── -->
                <TabsContent value="maintenance" class="mt-4">
                    <MaintenanceTab
                        :windows="windows"
                        :stats="stats"
                        :global-pause="globalPause"
                        :providers="providers"
                    />
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
