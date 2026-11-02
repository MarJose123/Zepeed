<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";
import { Sonner } from "@/components/ui/sonner";
import { useNotification } from "@/composables/useNotification";

const flashEventListener = ref();

const { notify } = useNotification();

onMounted(() => {
    flashEventListener.value = router.on("flash", (event) => {
        notify(event.detail.flash?.notification);
    });
});

onUnmounted(() => {
    if (flashEventListener.value) {
        flashEventListener.value();
    }
});
</script>

<template>
    <div>
        <slot />
        <Sonner richColors position="bottom-right" />
    </div>
</template>

<style scoped></style>
