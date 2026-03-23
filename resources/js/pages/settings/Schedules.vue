<script setup lang="ts">
import { Head, useForm, router } from "@inertiajs/vue3";
import {
    Plus,
    Trash2,
    Loader2,
    AlertTriangle,
} from "lucide-vue-next";
import { ref } from "vue";
import ProviderScheduleRow from "@/components/speedtest/ProviderScheduleRow.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from "@/components/ui/card";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import { Textarea } from "@/components/ui/textarea";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type {
    ProviderSchedule,
    MaintenanceWindow,
    Provider,
} from "@/types/provider";

defineProps<{
    providers: Provider[];
    schedules: ProviderSchedule[];
    windows: MaintenanceWindow[];
    globalPause: boolean;
}>();

const breadcrumbs: TBreadcrumbItem[] = [
    { title: "Settings", href: "#" },
    { title: "Schedules", href: route("speedtest.schedules.index", {}, false) },
];

const activeTab = ref<"schedules" | "maintenance">("schedules");

// Global pause
const globalPauseProcessing = ref(false);

function toggleGlobalPause() {
    globalPauseProcessing.value = true;
    router.post(
        route("settings.maintenance.global-pause", {}, false),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                globalPauseProcessing.value = false;
            },
        },
    );
}

// Maintenance window form
const showAddWindow = ref(false);

const windowForm = useForm({
    label: "",
    type: "one_time" as "indefinite" | "one_time" | "recurring",
    is_active: true,
    providers: ["all"] as string[],
    starts_at: "",
    ends_at: "",
    cron_expression: "",
    duration_minutes: 60,
    notes: "",
});

function submitWindow() {
    windowForm.post(route("settings.maintenance.store", {}, false), {
        preserveScroll: true,
        onSuccess: () => {
            showAddWindow.value = false;
            windowForm.reset();
        },
    });
}

function deleteWindow(window: MaintenanceWindow) {
    router.delete(
        route(
            "settings.maintenance.destroy",
            { maintenanceWindow: window.id },
            false,
        ),
        { preserveScroll: true },
    );
}

function windowTypeBadge(type: MaintenanceWindow["type"]) {
    return {
        indefinite: "secondary",
        one_time: "default",
        recurring: "outline",
    }[type] as "secondary" | "default" | "outline";
}
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
                            <CardTitle class="text-sm font-medium"
                                >Provider schedules</CardTitle
                            >
                            <CardDescription class="text-xs">
                                Click a provider row to edit its schedule
                            </CardDescription>
                        </CardHeader>

                        <!-- One row per schedule — first expanded, rest collapsed -->
                        <ProviderScheduleRow
                            v-for="(schedule, index) in schedules"
                            :key="schedule.provider_slug"
                            :schedule="schedule"
                            :default-open="index === 0"
                        />
                    </Card>
                </TabsContent>

                <!-- ── Maintenance tab ── -->
                <TabsContent value="maintenance" class="mt-4 space-y-3">
                    <!-- Global pause -->
                    <Card
                        :class="{
                            'border-destructive/50 bg-destructive/5':
                                globalPause,
                        }"
                    >
                        <CardContent
                            class="flex items-center justify-between p-4"
                        >
                            <div class="space-y-0.5">
                                <p class="font-semibold">Global pause</p>
                                <p class="text-muted-foreground text-sm">
                                    Suspend all providers until manually
                                    re-enabled
                                </p>
                            </div>
                            <Switch
                                :checked="globalPause"
                                :disabled="globalPauseProcessing"
                                @update:checked="toggleGlobalPause"
                            />
                        </CardContent>
                    </Card>

                    <!-- Active warning -->
                    <div
                        v-if="globalPause"
                        class="bg-destructive/10 border-destructive/30 flex items-center gap-2 rounded-lg border p-3"
                    >
                        <AlertTriangle
                            class="text-destructive h-4 w-4 shrink-0"
                        />
                        <p class="text-destructive text-sm font-medium">
                            Global pause is active — all speedtest runs are
                            suppressed.
                        </p>
                    </div>

                    <!-- Windows list header -->
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium">Scheduled windows</p>
                        <Dialog v-model:open="showAddWindow">
                            <DialogTrigger as-child>
                                <Button size="sm" variant="outline">
                                    <Plus class="mr-1.5 h-4 w-4" />
                                    Add window
                                </Button>
                            </DialogTrigger>
                            <DialogContent class="max-w-md">
                                <DialogHeader>
                                    <DialogTitle
                                        >New maintenance window</DialogTitle
                                    >
                                </DialogHeader>
                                <div class="space-y-4 py-2">
                                    <div class="space-y-1.5">
                                        <Label>Label</Label>
                                        <Input
                                            v-model="windowForm.label"
                                            placeholder="e.g. ISP maintenance"
                                        />
                                        <p
                                            v-if="windowForm.errors.label"
                                            class="text-destructive text-xs"
                                        >
                                            {{ windowForm.errors.label }}
                                        </p>
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label>Type</Label>
                                        <Select v-model="windowForm.type">
                                            <SelectTrigger
                                                ><SelectValue
                                                    placeholder="Select type"
                                            /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="one_time"
                                                    >One-time</SelectItem
                                                >
                                                <SelectItem value="recurring"
                                                    >Recurring</SelectItem
                                                >
                                                <SelectItem value="indefinite"
                                                    >Indefinite</SelectItem
                                                >
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <template
                                        v-if="windowForm.type === 'one_time'"
                                    >
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1.5">
                                                <Label>Starts at</Label>
                                                <Input
                                                    v-model="
                                                        windowForm.starts_at
                                                    "
                                                    type="datetime-local"
                                                />
                                                <p
                                                    v-if="
                                                        windowForm.errors
                                                            .starts_at
                                                    "
                                                    class="text-destructive text-xs"
                                                >
                                                    {{
                                                        windowForm.errors
                                                            .starts_at
                                                    }}
                                                </p>
                                            </div>
                                            <div class="space-y-1.5">
                                                <Label>Ends at</Label>
                                                <Input
                                                    v-model="windowForm.ends_at"
                                                    type="datetime-local"
                                                />
                                                <p
                                                    v-if="
                                                        windowForm.errors
                                                            .ends_at
                                                    "
                                                    class="text-destructive text-xs"
                                                >
                                                    {{
                                                        windowForm.errors
                                                            .ends_at
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </template>
                                    <template
                                        v-if="windowForm.type === 'recurring'"
                                    >
                                        <div class="space-y-1.5">
                                            <Label>Cron expression</Label>
                                            <Input
                                                v-model="
                                                    windowForm.cron_expression
                                                "
                                                placeholder="0 2 * * 0"
                                                class="font-mono"
                                            />
                                            <p
                                                class="text-muted-foreground text-xs"
                                            >
                                                e.g.
                                                <code class="font-mono"
                                                    >0 2 * * 0</code
                                                >
                                                = every Sunday at 02:00
                                            </p>
                                            <p
                                                v-if="
                                                    windowForm.errors
                                                        .cron_expression
                                                "
                                                class="text-destructive text-xs"
                                            >
                                                {{
                                                    windowForm.errors
                                                        .cron_expression
                                                }}
                                            </p>
                                        </div>
                                        <div class="space-y-1.5">
                                            <Label>Duration (minutes)</Label>
                                            <Input
                                                v-model.number="
                                                    windowForm.duration_minutes
                                                "
                                                type="number"
                                                min="1"
                                                max="1440"
                                                placeholder="60"
                                            />
                                            <p
                                                v-if="
                                                    windowForm.errors
                                                        .duration_minutes
                                                "
                                                class="text-destructive text-xs"
                                            >
                                                {{
                                                    windowForm.errors
                                                        .duration_minutes
                                                }}
                                            </p>
                                        </div>
                                    </template>
                                    <div class="space-y-1.5">
                                        <Label>Applies to</Label>
                                        <Select
                                            :model-value="
                                                windowForm.providers[0]
                                            "
                                            @update:model-value="
                                                windowForm.providers = [$event]
                                            "
                                        >
                                            <SelectTrigger
                                                ><SelectValue
                                                    placeholder="Select providers"
                                            /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all"
                                                    >All providers</SelectItem
                                                >
                                                <SelectItem
                                                    v-for="provider in providers"
                                                    :key="provider.slug"
                                                    :value="provider.slug"
                                                >
                                                    {{ provider.name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label
                                            >Notes
                                            <span class="text-muted-foreground"
                                                >(optional)</span
                                            ></Label
                                        >
                                        <Textarea
                                            v-model="windowForm.notes"
                                            placeholder="e.g. ISP ticket #12345"
                                            :rows="2"
                                        />
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button
                                        variant="outline"
                                        @click="showAddWindow = false"
                                        >Cancel</Button
                                    >
                                    <Button
                                        :disabled="windowForm.processing"
                                        @click="submitWindow"
                                    >
                                        <Loader2
                                            v-if="windowForm.processing"
                                            class="mr-2 h-4 w-4 animate-spin"
                                        />
                                        Create window
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-if="windows.length === 0"
                        class="border-border rounded-lg border border-dashed py-10 text-center"
                    >
                        <p class="text-muted-foreground text-sm">
                            No maintenance windows configured.
                        </p>
                    </div>

                    <!-- Windows list -->
                    <Card v-for="window in windows" :key="window.id">
                        <CardContent
                            class="flex items-start justify-between gap-4 p-4"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium">
                                        {{ window.label }}
                                    </p>
                                    <Badge
                                        :variant="windowTypeBadge(window.type)"
                                        class="text-xs"
                                        >{{ window.type_label }}</Badge
                                    >
                                    <Badge
                                        v-if="window.is_currently_active"
                                        variant="destructive"
                                        class="text-xs"
                                        >Active</Badge
                                    >
                                </div>
                                <p class="text-muted-foreground text-xs">
                                    <template v-if="window.type === 'one_time'"
                                        >{{ window.starts_at }} →
                                        {{ window.ends_at }}</template
                                    >
                                    <template
                                        v-else-if="window.type === 'recurring'"
                                    >
                                        <code class="font-mono">{{
                                            window.cron_expression
                                        }}</code>
                                        for {{ window.duration_minutes }} min
                                    </template>
                                    <template v-else
                                        >Until manually disabled</template
                                    >
                                </p>
                                <p class="text-muted-foreground text-xs">
                                    {{
                                        window.covers_all
                                            ? "All providers"
                                            : window.providers.join(", ")
                                    }}
                                </p>
                                <p
                                    v-if="window.notes"
                                    class="text-muted-foreground text-xs italic"
                                >
                                    {{ window.notes }}
                                </p>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="text-muted-foreground hover:text-destructive h-8 w-8 shrink-0"
                                @click="deleteWindow(window)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </CardContent>
                    </Card>

                    <p class="text-muted-foreground pt-2 text-center text-xs">
                        Runs skipped during any active window are logged with a
                        <code class="font-mono">skipped</code> status in
                        Results.
                    </p>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
