<script setup lang="ts">
import { Star } from "@lucide/vue";
import { Button } from "@/components/ui/button";

defineProps<{
    onStar: () => void;
    onNotNow: () => void;
}>();
</script>

<template>
    <div
        class="bg-popover text-popover-foreground flex w-full max-w-[340px] items-start gap-3 rounded-lg border px-3 py-3 shadow-md"
        role="dialog"
        aria-label="Star the GitHub repository"
    >
        <Star class="mt-0.5 size-5 shrink-0 fill-amber-400 text-amber-400" />
        <div class="grid flex-1 gap-1.5">
            <p class="text-sm font-medium leading-none">
                Enjoying this project?
            </p>
            <p class="text-sm text-muted-foreground">
                If this project is useful to you, consider giving it a ⭐ on
                GitHub. It helps support the project and lets other developers
                discover it.
            </p>
            <div class="mt-2 flex justify-end gap-2">
                <Button
                    variant="secondary"
                    size="sm"
                    class="h-8"
                    @click="onNotNow"
                >
                    Not now
                </Button>
                <Button size="sm" class="h-8" @click="onStar">
                    <Star class="fill-current" />
                    Star on GitHub
                </Button>
            </div>
        </div>
    </div>
</template>
