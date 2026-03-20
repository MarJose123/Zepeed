<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import type { TBreadcrumbItem } from "@/types";

const breadcrumbs: TBreadcrumbItem[] = [
    {
        title: "Dashboard",
        href: route("dashboard", {}, false),
    },
];
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="bg-muted/50 aspect-video rounded-xl" />
                <div class="bg-muted/50 aspect-video rounded-xl" />
                <div class="bg-muted/50 aspect-video rounded-xl" />
            </div>
            <div
                class="bg-muted/50 min-h-screen flex-1 rounded-xl md:min-h-min"
            />
        </div>
    </AppLayout>
</template>

<style scoped></style>
