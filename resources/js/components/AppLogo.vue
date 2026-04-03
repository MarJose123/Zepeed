<script setup lang="ts">
import AppLogoIcon from "@/components/AppLogoIcon.vue";
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon />
    </div>
    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">Zepeed</span>
        <span class="truncate text-xs">Internet Speed Tracker</span>
    </div>
</template>

<style scoped></style>
