<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { Bell, Pencil, Trash2 } from "@lucide/vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import type { Apprise } from "@/types/apprise";

const props = defineProps<{
    apprise: Apprise;
    selected: boolean;
}>();

const emit = defineEmits<{
    select: [];
    edit: [];
    delete: [];
}>();

const lastFiredLabel = (a: Apprise) => {
    if (!a.last_fired_at) {
        return "Never";
    }

    const diff = Date.now() - new Date(a.last_fired_at).getTime();
    const mins = Math.floor(diff / 60_000);

    if (mins < 60) {
        return `${mins} min ago`;
    }

    const hrs = Math.floor(mins / 60);

    if (hrs < 24) {
        return `${hrs}h ago`;
    }

    return `${Math.floor(hrs / 24)}d ago`;
};

function testApprise() {
    router.post(
        route(
            "speedtest.integration.apprise.test",
            { apprise: props.apprise.id },
            false,
        ),
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <div
        class="border-border flex cursor-pointer flex-col overflow-hidden rounded-md border transition-colors"
        :class="{
            'border-primary/60 bg-primary/2': selected,
            'hover:border-border-secondary': !selected,
        }"
        @click="emit('select')"
    >
        <!-- Top -->
        <div class="flex-1 p-4">
            <div class="mb-4 flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="bg-muted border-border flex h-8 w-8 shrink-0 items-center justify-center rounded-md border"
                    >
                        <Bell class="text-muted-foreground size-4" />
                    </div>
                    <div>
                        <div class="text-sm font-medium leading-none">
                            {{ apprise.name }}
                        </div>
                        <code
                            class="mt-1 block text-xs text-muted-foreground"
                            >{{
                                apprise.has_credentials
                                    ? `basic auth · ${apprise.username}`
                                    : "no auth"
                            }}</code
                        >
                    </div>
                </div>
                <Badge
                    :class="
                        apprise.is_active
                            ? 'border-green-600/20 bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-400'
                            : 'border-border bg-muted text-muted-foreground border'
                    "
                    variant="outline"
                    class="text-xs px-1.5 py-0"
                >
                    {{ apprise.is_active ? "active" : "inactive" }}
                </Badge>
            </div>

            <!-- URL preview -->
            <p class="text-muted-foreground mb-3 truncate font-mono text-xs">
                {{ apprise.url_preview }}
            </p>

            <!-- Tags -->
            <div class="mb-4 flex min-h-5 flex-wrap gap-1.5">
                <Badge
                    v-for="tag in apprise.tags"
                    :key="tag"
                    variant="outline"
                    class="border-purple-300/50 bg-purple-50 text-xs text-purple-700 dark:border-purple-800 dark:bg-purple-950 dark:text-purple-300"
                >
                    {{ tag }}
                </Badge>
                <span
                    v-if="apprise.tags.length === 0"
                    class="text-xs text-muted-foreground"
                >
                    no tags — notifies all configured services
                </span>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-2">
                <div class="bg-muted/50 rounded-md p-2.5">
                    <p class="text-xs text-muted-foreground">Last fired</p>
                    <p class="mt-0.5 text-sm font-semibold">
                        {{ lastFiredLabel(apprise) }}
                    </p>
                </div>
                <div class="bg-muted/50 rounded-md p-2.5">
                    <p class="text-xs text-muted-foreground">Timeout</p>
                    <p class="mt-0.5 text-sm font-semibold">
                        {{ apprise.timeout }}s
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer actions -->
        <div
            class="border-border flex items-center gap-1.5 border-t p-2.5"
            @click.stop
        >
            <Button
                variant="outline"
                size="sm"
                class="h-8 flex-1 text-xs"
                @click="testApprise"
            >
                Test
            </Button>
            <Button
                variant="outline"
                size="sm"
                class="h-8 flex-1 text-xs"
                @click="emit('edit')"
            >
                <Pencil class="mr-1.5 size-3.5" />
                Edit
            </Button>
            <Button
                variant="ghost"
                size="icon"
                class="text-muted-foreground hover:text-destructive h-8 w-8"
                @click="emit('delete')"
            >
                <Trash2 class="size-3.5" />
            </Button>
        </div>
    </div>
</template>
