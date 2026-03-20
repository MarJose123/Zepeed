<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import ResetPasswordForm from "@/components/ResetPasswordForm.vue";
import GuestLayout from "@/layouts/GuestLayout.vue";

defineProps<{
    token?: string;
    email?: string;
}>();
</script>
<template>
    <Head title="Reset Password" />
    <GuestLayout
        class="bg-muted flex min-h-svh flex-col items-center justify-center p-6 md:p-10"
    >
        <div class="w-full max-w-sm md:max-w-4xl">
            <ResetPasswordForm :email="email" :token="token" />
        </div>
    </GuestLayout>
</template>
