<script setup lang="ts">
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import ApiTokenCreateDialog from "@/components/api-token/ApiTokenCreateDialog.vue";
import ApiTokenNewModal from "@/components/api-token/ApiTokenNewModal.vue";
import ApiTokenRevokeAllDialog from "@/components/api-token/ApiTokenRevokeAllDialog.vue";
import ApiTokenTable from "@/components/api-token/ApiTokenTable.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import SettingsLayout from "@/layouts/SettingsLayout.vue";
import type { TBreadcrumbItem } from "@/types";
import type { ApiToken } from "@/types/api-token";

defineProps<{
    tokens: ApiToken[];
}>();

const page = usePage();

const newToken = computed(
    () => (page.flash?.new_token as string | null) ?? null,
);

const breadcrumbItems: TBreadcrumbItem[] = [
    {
        title: "Settings",
        href: route("profile.edit", {}, false),
    },
    {
        title: "API Tokens",
        href: route("api-tokens.index", {}, false),
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="API Tokens" />
        <h1 class="sr-only">API Tokens</h1>

        <SettingsLayout>
            <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xl font-bold tracking-tight">
                            API Tokens
                        </p>
                        <p class="text-sm text-muted-foreground mt-1">
                            Manage personal API tokens for programmatic access
                            to Zepeed.
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <ApiTokenCreateDialog />
                        <ApiTokenRevokeAllDialog
                            :disabled="tokens.length === 0"
                        />
                    </div>
                </div>

                <ApiTokenTable :tokens="tokens" />

                <ApiTokenNewModal :token="newToken" />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

<style scoped></style>
