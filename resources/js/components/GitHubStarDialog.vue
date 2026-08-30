<script setup lang="ts">
import { markRaw, watch } from "vue";
import { toast } from "vue-sonner";
import GitHubStarToast from "@/components/GitHubStarToast.vue";
import { useGitHubStarPrompt } from "@/composables/useGitHubStarPrompt";

const { isOpen, star, dismiss } = useGitHubStarPrompt();

let toastId: string | number | undefined;

/**
 * Render the GitHub star prompt as a persistent toast instead of a modal.
 *
 * The toast never auto-closes (`duration: Infinity`) and is not dismissible
 * by swiping (`dismissible: false`). Toaster-level `closeButton` stays off,
 * so there is no independent close affordance — it only closes when the
 * user explicitly picks "Star on GitHub" (opens the repo, no session
 * dismissal) or "Not now" (marks it dismissed for the rest of the session).
 */
watch(
    isOpen,
    (open) => {
        if (open) {
            toastId = toast.custom(markRaw(GitHubStarToast), {
                componentProps: {
                    onStar: () => {
                        toast.dismiss(toastId);
                        star();
                    },
                    onNotNow: () => {
                        toast.dismiss(toastId);
                        dismiss();
                    },
                },
                // Place the prompt at the bottom-center of the viewport. The
                // sonner container is a small fixed box around just this
                // toast, so it does not shield the rest of the screen — the
                // user can keep interacting with the page freely.
                position: "bottom-center",
                duration: Infinity,
                dismissible: false,
                closeButton: false,
            });
        } else if (toastId !== undefined) {
            toast.dismiss(toastId);
            toastId = undefined;
        }
    },
    { immediate: true },
);
</script>

<template>
    <span class="hidden" />
</template>
