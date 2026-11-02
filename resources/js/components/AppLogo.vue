<script setup lang="ts">
import AppLogoIcon from "@/components/AppLogoIcon.vue";
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon />
    </div>
    <div class="grid flex-1 text-left leading-tight">
        <span class="truncate text-sm font-semibold">Zepeed</span>
        <span class="truncate text-xs text-muted-foreground"
            >Speed Tracker</span
        >
    </div>
</template>

<style scoped></style>
