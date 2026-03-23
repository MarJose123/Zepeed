<script setup lang="ts">
import { router, useForm } from "@inertiajs/vue3";
import {
    AlertTriangle,
    CalendarClock,
    Clock,
    Infinity,
    Loader2,
    Plus,
    RefreshCw,
    Trash2,
} from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Empty } from "@/components/ui/empty";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";
import { Switch } from "@/components/ui/switch";
import { Textarea } from "@/components/ui/textarea";
import type {
    MaintenanceWindow,
    MaintenanceWindowStats,
    Provider,
} from "@/types/provider";

const props = defineProps<{
    windows: MaintenanceWindow[];
    stats: MaintenanceWindowStats;
    globalPause: boolean;
    providers: Provider[];
}>();

// ── Global pause ──────────────────────────────────────────────────────────────
const globalPauseProcessing = ref(false);

const toggleGlobalPause = () => {
    globalPauseProcessing.value = true;
    router.post(
        route("speedtest.maintenance.global-pause", {}, false),
        {},
        {
            preserveScroll: true,
            onSuccess: () => router.reload(),
            onFinish: () => {
                globalPauseProcessing.value = false;
            },
        },
    );
};

// ── Type icon map ─────────────────────────────────────────────────────────────
const typeIcon = (type: MaintenanceWindow["type"]) => {
    return {
        one_time: CalendarClock,
        recurring: RefreshCw,
        indefinite: Infinity,
    }[type];
};

// ── Type badge variant ────────────────────────────────────────────────────────
const typeBadgeVariant = (type: MaintenanceWindow["type"]) => {
    return {
        one_time: "secondary",
        recurring: "outline",
        indefinite: "secondary",
    }[type] as "secondary" | "outline";
};

// ── Left accent bar colour ────────────────────────────────────────────────────
const typeAccent = (window: MaintenanceWindow) => {
    if (window.is_currently_active) return "bg-destructive";
    return {
        one_time: "bg-blue-400",
        recurring: "bg-green-500",
        indefinite: "bg-muted-foreground/40",
    }[window.type];
};

// ── Provider label ────────────────────────────────────────────────────────────
const providerLabel = (window: MaintenanceWindow) => {
    if (window.covers_all) return "All providers";
    return window.providers
        .map(
            (slug) =>
                props.providers.find((p) => p.slug === slug)?.name ?? slug,
        )
        .join(", ");
};

// ── Delete ────────────────────────────────────────────────────────────────────
const deleteWindow = (window: MaintenanceWindow) => {
    router.delete(
        route(
            "speedtest.maintenance.destroy",
            { maintenanceWindow: window.id },
            false,
        ),
        {
            preserveScroll: true,
            onSuccess: () => router.reload(),
        },
    );
};

// ── Add window form ───────────────────────────────────────────────────────────
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

const submitWindow = () => {
    windowForm.post(route("speedtest.maintenance.store", {}, false), {
        preserveScroll: true,
        onSuccess: () => {
            showAddWindow.value = false;
            windowForm.reset();
            router.reload();
        },
    });
};

const nowLocalIso = computed(() => {
    const now = new Date();
    const offset = now.getTimezoneOffset() * 60_000;
    return new Date(now.getTime() - offset).toISOString().slice(0, 16);
});

const endsAtMin = computed(() => {
    if (!windowForm.starts_at) return nowLocalIso.value;

    // Add 1 minute to starts_at so ends_at must be strictly after
    const start = new Date(windowForm.starts_at);
    start.setMinutes(start.getMinutes() + 1);
    const offset = start.getTimezoneOffset() * 60_000;
    return new Date(start.getTime() - offset).toISOString().slice(0, 16);
});

const onStartsAtChange = () => {
    const selected = new Date(windowForm.starts_at);
    const now = new Date();

    // If selected time is in the past, reset to current time
    if (selected <= now) {
        const offset = now.getTimezoneOffset() * 60_000;
        windowForm.starts_at = new Date(now.getTime() - offset)
            .toISOString()
            .slice(0, 16);
    }

    // Clear ends_at if it's now invalid
    if (windowForm.ends_at && windowForm.ends_at <= windowForm.starts_at) {
        windowForm.ends_at = "";
    }
};

const onEndsAtChange = () => {
    if (!windowForm.ends_at) return;

    const selected = new Date(windowForm.ends_at);
    const minAllowed = windowForm.starts_at
        ? new Date(new Date(windowForm.starts_at).getTime() + 60_000)
        : new Date();

    // If selected end time is before or equal to starts_at, reset it
    if (selected <= minAllowed) {
        const offset = minAllowed.getTimezoneOffset() * 60_000;
        windowForm.ends_at = new Date(minAllowed.getTime() - offset)
            .toISOString()
            .slice(0, 16);
    }
};

watch(
    () => windowForm.starts_at,
    () => {
        // Reset ends_at if it's now before the new starts_at min
        if (
            windowForm.ends_at &&
            windowForm.ends_at <= (windowForm.starts_at ?? "")
        ) {
            windowForm.ends_at = "";
        }
    },
);
</script>

<template>
    <div class="space-y-4">
        <!-- Stats bar + global pause -->
        <Card class="overflow-hidden p-0">
            <!-- Stats bar — 3 metric cards -->
            <div class="grid grid-cols-3 divide-x divide-border">
                <div class="px-5 py-4">
                    <p class="text-2xl font-medium">{{ stats.total }}</p>
                    <p class="text-muted-foreground mt-0.5 text-xs">
                        Scheduled windows
                    </p>
                </div>
                <div class="px-5 py-4">
                    <p
                        class="text-2xl font-medium"
                        :class="
                            stats.currently_active > 0 ? 'text-destructive' : ''
                        "
                    >
                        {{ stats.currently_active }}
                    </p>
                    <p class="text-muted-foreground mt-0.5 text-xs">
                        Currently active
                    </p>
                </div>
                <div class="px-5 py-4">
                    <p
                        class="text-2xl font-medium"
                        :class="
                            globalPause
                                ? 'text-destructive'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ globalPause ? "ON" : "OFF" }}
                    </p>
                    <p class="text-muted-foreground mt-0.5 text-xs">
                        Global pause
                    </p>
                </div>
            </div>

            <Separator />

            <!-- Global pause toggle row -->
            <div
                class="flex items-center justify-between px-5 py-4 transition-colors"
                :class="{ 'bg-destructive/5': globalPause }"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
                        :class="globalPause ? 'bg-destructive/10' : 'bg-muted'"
                    >
                        <AlertTriangle
                            class="h-4 w-4"
                            :class="
                                globalPause
                                    ? 'text-destructive'
                                    : 'text-muted-foreground'
                            "
                        />
                    </div>
                    <div>
                        <p class="text-sm font-medium">Global pause</p>
                        <p class="text-muted-foreground text-xs">
                            {{
                                globalPause
                                    ? "All speedtest runs are currently suppressed"
                                    : "Suspend all providers until manually re-enabled"
                            }}
                        </p>
                    </div>
                </div>
                <Switch
                    :model-value="globalPause"
                    :disabled="globalPauseProcessing"
                    @update:modelValue="() => toggleGlobalPause()"
                />
            </div>
        </Card>

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
                        <DialogTitle>New maintenance window</DialogTitle>
                    </DialogHeader>

                    <div class="space-y-4 py-2">
                        <!-- Label -->
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

                        <!-- Type -->
                        <div class="space-y-1.5">
                            <Label>Type</Label>
                            <Select v-model="windowForm.type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
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

                        <!-- One-time fields -->
                        <template v-if="windowForm.type === 'one_time'">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <Label>Starts at</Label>
                                    <Input
                                        v-model="windowForm.starts_at"
                                        type="datetime-local"
                                        :min="nowLocalIso"
                                        @change="onStartsAtChange"
                                    />
                                    <p
                                        v-if="windowForm.errors.starts_at"
                                        class="text-destructive text-xs"
                                    >
                                        {{ windowForm.errors.starts_at }}
                                    </p>
                                </div>
                                <div class="space-y-1.5">
                                    <Label>Ends at</Label>
                                    <Input
                                        v-model="windowForm.ends_at"
                                        type="datetime-local"
                                        :min="endsAtMin"
                                        @change="onEndsAtChange"
                                    />ƒ
                                    <p
                                        v-if="windowForm.errors.ends_at"
                                        class="text-destructive text-xs"
                                    >
                                        {{ windowForm.errors.ends_at }}
                                    </p>
                                </div>
                            </div>
                        </template>

                        <!-- Recurring fields -->
                        <template v-if="windowForm.type === 'recurring'">
                            <div class="space-y-1.5">
                                <Label>Cron expression</Label>
                                <Input
                                    v-model="windowForm.cron_expression"
                                    placeholder="0 2 * * 0"
                                    class="font-mono"
                                />
                                <p class="text-muted-foreground text-xs">
                                    e.g.
                                    <code class="font-mono">0 2 * * 0</code> =
                                    every Sunday at 02:00
                                </p>
                                <p
                                    v-if="windowForm.errors.cron_expression"
                                    class="text-destructive text-xs"
                                >
                                    {{ windowForm.errors.cron_expression }}
                                </p>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Duration (minutes)</Label>
                                <Input
                                    v-model.number="windowForm.duration_minutes"
                                    type="number"
                                    min="1"
                                    max="1440"
                                    placeholder="60"
                                />
                                <p
                                    v-if="windowForm.errors.duration_minutes"
                                    class="text-destructive text-xs"
                                >
                                    {{ windowForm.errors.duration_minutes }}
                                </p>
                            </div>
                        </template>

                        <!-- Applies to -->
                        <div class="space-y-1.5">
                            <Label>Applies to</Label>
                            <Select
                                :model-value="windowForm.providers[0]"
                                @update:model-value="
                                    windowForm.providers = [$event]
                                "
                            >
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Select providers"
                                    />
                                </SelectTrigger>
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

                        <!-- Notes -->
                        <div class="space-y-1.5">
                            <Label>
                                Notes
                                <span class="text-muted-foreground"
                                    >(optional)</span
                                >
                            </Label>
                            <Textarea
                                v-model="windowForm.notes"
                                placeholder="e.g. ISP ticket #12345"
                                :rows="2"
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="secondary"
                            @click="showAddWindow = false"
                        >
                            Cancel
                        </Button>
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
        <Empty v-if="windows.length === 0" class="border border-dashed">
            <Clock class="text-muted-foreground h-6 w-6" />
            <div>
                <p class="text-muted-foreground text-sm">
                    No maintenance windows configured.
                </p>
                <p class="text-muted-foreground mt-0.5 text-xs">
                    Add a window to pause speedtest runs during planned
                    downtime.
                </p>
            </div>
        </Empty>

        <!-- Windows list -->
        <Card
            v-for="window in windows"
            :key="window.id"
            class="overflow-hidden p-0"
        >
            <CardContent class="flex items-start gap-0 p-0">
                <!-- Left accent bar -->
                <div
                    class="w-1 shrink-0 self-stretch rounded-l-lg"
                    :class="typeAccent(window)"
                />

                <div
                    class="flex flex-1 items-start justify-between gap-4 px-4 py-3.5"
                >
                    <div class="flex items-start gap-3">
                        <!-- Type icon -->
                        <div
                            class="bg-muted mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
                        >
                            <component
                                :is="typeIcon(window.type)"
                                class="text-muted-foreground h-4 w-4"
                            />
                        </div>

                        <!-- Window info -->
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-medium">
                                    {{ window.label }}
                                </p>
                                <Badge
                                    v-if="window.is_currently_active"
                                    variant="destructive"
                                    class="text-[10px]"
                                >
                                    Active
                                </Badge>
                                <Badge
                                    :variant="typeBadgeVariant(window.type)"
                                    class="text-[10px]"
                                >
                                    {{ window.type_label }}
                                </Badge>
                            </div>

                            <p class="text-muted-foreground text-xs">
                                <template v-if="window.type === 'one_time'">
                                    {{ window.starts_at }} →
                                    {{ window.ends_at }}
                                </template>
                                <template
                                    v-else-if="window.type === 'recurring'"
                                >
                                    <code class="font-mono">{{
                                        window.cron_expression
                                    }}</code>
                                    · {{ window.duration_minutes }} min
                                </template>
                                <template v-else>
                                    Until manually disabled
                                </template>
                            </p>

                            <p class="text-muted-foreground text-xs">
                                {{ providerLabel(window) }}
                            </p>

                            <p
                                v-if="window.notes"
                                class="text-muted-foreground text-xs italic"
                            >
                                {{ window.notes }}
                            </p>
                        </div>
                    </div>

                    <!-- Delete button -->
                    <Button
                        variant="ghost"
                        size="icon"
                        class="text-muted-foreground hover:text-destructive h-8 w-8 shrink-0"
                        @click="deleteWindow(window)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Footer note -->
        <p class="text-muted-foreground pt-1 text-center text-xs">
            Runs skipped during any active window are logged with a
            <code class="font-bold">skipped</code> status in Results.
        </p>
    </div>
</template>
