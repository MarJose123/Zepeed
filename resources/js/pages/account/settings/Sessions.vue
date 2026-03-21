<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import WebUserSession from "@/components/WebUserSession.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import SettingsLayout from "@/layouts/SettingsLayout.vue";
import type { TBreadcrumbItem, WebSession } from "@/types";

defineProps<{
    userWebSession: WebSession[];
}>();

const breadcrumbItems: TBreadcrumbItem[] = [
    {
        title: "Settings",
        href: route("profile.edit", {}, false),
    },
    {
        title: "Sessions",
        href: route("user-sessions.edit", {}, false),
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="User Sessions" />
        <h1 class="sr-only">User Sessions</h1>
        <SettingsLayout>
            <div class="flex w-full">
                <WebUserSession :webSessions="userWebSession" />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

<style scoped></style>
