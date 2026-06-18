<script setup lang="ts">
import { Check, Copy } from "@lucide/vue";
import { computed, ref, watch } from "vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";

const props = defineProps<{
    token: string | null;
}>();

const open = ref(false);
const copied = ref(false);

watch(
    () => props.token,
    (val) => {
        if (val) open.value = true;
    },
);

const maskedToken = computed(() => {
    if (!props.token) return "";

    return props.token.slice(0, 8) + "•".repeat(20) + props.token.slice(-6);
});

async function copy(): Promise<void> {
    if (!props.token) return;

    await navigator.clipboard.writeText(props.token);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}
</script>

<template>
    <Dialog :open="open" @update:open="(v) => !v && (open = false)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle class="text-sm font-medium">
                    Your New API Token
                </DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <p class="text-[11px] text-muted-foreground">
                    Copy and store this token securely. It will not be shown
                    again after you close this dialog.
                </p>

                <div
                    class="flex items-center gap-2 rounded-md border bg-muted px-3 py-2"
                >
                    <code class="flex-1 truncate text-xs">{{
                        maskedToken
                    }}</code>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-7 shrink-0"
                        @click="copy"
                    >
                        <Check
                            v-if="copied"
                            class="h-3.5 w-3.5 text-green-500"
                        />
                        <Copy v-else class="h-3.5 w-3.5" />
                    </Button>
                </div>

                <div
                    class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-800 dark:bg-amber-950"
                >
                    <p class="text-xs text-amber-700 dark:text-amber-300">
                        Use this token in API requests as:
                        <span
                            class="block mt-1 text-[11px] text-amber-600 dark:text-amber-400"
                        >
                            Authorization: Bearer &lt;token&gt;
                        </span>
                    </p>
                </div>
            </div>

            <div class="pt-2">
                <Button class="w-full" size="sm" @click="open = false">
                    {{ copied ? "Copied — Close" : "I've saved the token" }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped></style>
