<script setup lang="ts">
import { Star } from "@lucide/vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { useGitHubStarPrompt } from "@/composables/useGitHubStarPrompt";

const { isOpen, dismiss, star } = useGitHubStarPrompt();

/**
 * Any user-initiated close (Esc, overlay click, close button, "Not now")
 * counts as a dismissal and keeps the dialog hidden for the rest of the
 * session. The "Star on GitHub" button closes without marking a dismissal,
 * so the prompt may return on a later day.
 */
function handleOpenChange(open: boolean): void {
    if (!open) {
        dismiss();

        return;
    }

    isOpen.value = open;
}
</script>

<template>
    <Dialog :open="isOpen" @update:open="handleOpenChange">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Enjoying this project?</DialogTitle>
                <DialogDescription>
                    If this project is useful to you, consider giving it a ⭐ on
                    GitHub. It helps support the project and lets other
                    developers discover it.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Not now</Button>
                </DialogClose>
                <Button @click="star">
                    <Star class="fill-current" />
                    Star on GitHub
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
