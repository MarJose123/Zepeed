<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";
import { Toaster } from "vue-sonner";
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
        <Toaster richColors position="bottom-right" />
    </div>
</template>

<style scoped></style>
