<script setup lang="ts">
import { Form, Head } from "@inertiajs/vue3";
import { ShieldBan, ShieldCheck } from "lucide-vue-next";
import { onUnmounted, ref } from "vue";
import TwoFactorRecoveryCodes from "@/components/TwoFactorRecoveryCodes.vue";
import TwoFactorSetupModal from "@/components/TwoFactorSetupModal.vue";
import { Button } from "@/components/ui/button";
import { useTwoFactorAuth } from "@/composables/useTwoFactorAuth";
import AppLayout from "@/layouts/AppLayout.vue";
import SettingsLayout from "@/layouts/SettingsLayout.vue";
import type { TBreadcrumbItem } from "@/types";

type Props = {
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const breadcrumbItems: TBreadcrumbItem[] = [
    {
        title: "Settings",
        href: route("profile.edit", {}, false),
    },
    {
        title: "Two Factor Authentication",
        href: route("two-factor.show", {}, false),
    },
];

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => {
    clearTwoFactorAuthData();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Two-Factor Authentication" />

        <h1 class="sr-only">Two-Factor Authentication Settings</h1>
        <SettingsLayout>
            <div
                v-if="!twoFactorEnabled"
                class="flex flex-col items-start justify-start space-y-4 max-w-lg"
            >
                <p class="text-muted-foreground text-pretty md:text-balance">
                    When you enable two-factor authentication, you will be
                    prompted for a secure pin during login. This pin can be
                    retrieved from a TOTP-supported application on your phone.
                </p>

                <div>
                    <Button v-if="hasSetupData" @click="showSetupModal = true">
                        <ShieldCheck />Continue Setup
                    </Button>
                    <Form
                        v-else
                        method="post"
                        :action="route('two-factor.enable')"
                        @success="showSetupModal = true"
                        #default="{ processing }"
                    >
                        <Button type="submit" :disabled="processing">
                            <ShieldCheck />Enable 2FA</Button
                        >
                    </Form>
                </div>
            </div>

            <div
                v-else
                class="flex flex-col items-start justify-start space-y-4"
            >
                <p class="text-muted-foreground text-pretty md:text-balance">
                    With two-factor authentication enabled, you will be prompted
                    for a secure, random pin during login, which you can
                    retrieve from the TOTP-supported application on your phone.
                </p>

                <TwoFactorRecoveryCodes />

                <div class="relative inline">
                    <Form
                        method="delete"
                        :action="route('two-factor.disable')"
                        #default="{ processing }"
                    >
                        <Button
                            variant="destructive"
                            type="submit"
                            :disabled="processing"
                        >
                            <ShieldBan />
                            Disable 2FA
                        </Button>
                    </Form>
                </div>
            </div>

            <TwoFactorSetupModal
                v-model:isOpen="showSetupModal"
                :requiresConfirmation="requiresConfirmation"
                :twoFactorEnabled="twoFactorEnabled"
            />
        </SettingsLayout>
    </AppLayout>
</template>

<style scoped></style>
