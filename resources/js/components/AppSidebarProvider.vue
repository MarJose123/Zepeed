<script setup lang="ts">
import { SidebarProvider } from "@/components/ui/sidebar";
</script>

<template>
    <SidebarProvider storage-key="sidebar">
        <slot />
    </SidebarProvider>
</template>
