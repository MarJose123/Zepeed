<script setup lang="ts">
import { AlertCircle, CheckCircle2, Loader2, X } from "@lucide/vue";
import { Button } from "@/components/ui/button";
import type { ProviderTestState } from "@/types/provider";

defineProps<{
    state: ProviderTestState;
}>();

const emit = defineEmits<{
    cancel: [];
}>();
</script>

<template>
    <!-- Running -->
    <div
        v-if="state.status === 'pending' || state.status === 'running'"
        class="flex items-center gap-2"
    >
        <Loader2 class="h-4 w-4 animate-spin text-muted-foreground" />
        <span class="text-xs text-muted-foreground">Testing…</span>
        <Button
            type="button"
            variant="ghost"
            size="sm"
            class="h-6 px-2 text-xs text-muted-foreground hover:text-destructive"
            @click="emit('cancel')"
        >
            <X class="mr-1 h-3 w-3" />
            Cancel
        </Button>
    </div>

    <!-- Completed -->
    <div
        v-else-if="state.status === 'completed' && state.result"
        class="flex items-center gap-1.5"
    >
        <CheckCircle2
            class="h-4 w-4 shrink-0 text-green-600 dark:text-green-500"
        />
        <span class="text-xs font-medium tabular-nums">
            ↓&nbsp;{{ state.result.download_mbps }}&nbsp;Mbps
        </span>
        <span class="text-[11px] text-muted-foreground">·</span>
        <span class="text-xs font-medium tabular-nums">
            ↑&nbsp;{{ state.result.upload_mbps }}&nbsp;Mbps
        </span>
        <span class="text-[11px] text-muted-foreground">·</span>
        <span class="text-xs tabular-nums text-muted-foreground">
            ↔ {{ state.result.ping_ms }}&nbsp;ms
        </span>
    </div>

    <!-- Failed / exception -->
    <div
        v-else-if="state.status === 'failed'"
        class="flex items-center gap-1.5"
    >
        <AlertCircle class="h-4 w-4 shrink-0 text-destructive" />
        <span class="text-xs text-destructive line-clamp-1">
            {{ state.errorMessage ?? "Test failed" }}
        </span>
    </div>

    <!-- Skipped -->
    <div
        v-else-if="state.status === 'skipped'"
        class="flex items-center gap-1.5"
    >
        <AlertCircle class="h-4 w-4 shrink-0 text-amber-500" />
        <span class="text-xs text-amber-600 dark:text-amber-400">
            Skipped — maintenance window active
        </span>
    </div>

    <!-- Cancelled -->
    <div
        v-else-if="state.status === 'cancelled'"
        class="flex items-center gap-1.5"
    >
        <X class="h-4 w-4 shrink-0 text-muted-foreground" />
        <span class="text-xs text-muted-foreground">Cancelled</span>
    </div>
</template>
