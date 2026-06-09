<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";
import MaintenanceTab from "@/components/speedtest/MaintenanceTab.vue";
import ProviderScheduleGroup from "@/components/speedtest/ProviderScheduleGroup.vue";
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
    ProviderWithSchedules,
} from "@/types/provider";

defineProps<{
    providers: ProviderWithSchedules[];
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
                <h1 class="text-xl font-bold tracking-tight">Schedules</h1>
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
                                        Organize multiple schedules per provider
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>

                        <div>
                            <ProviderScheduleGroup
                                v-for="(provider, index) in providers"
                                :key="provider.slug"
                                :provider="provider"
                                :default-open="index === 0"
                            />
                        </div>
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
