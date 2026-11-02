<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import ForgotPasswordForm from "@/components/ForgotPasswordForm.vue";
import GuestLayout from "@/layouts/GuestLayout.vue";

defineProps<{
    status?: string;
}>();
</script>
<template>
    <Head title="Forgot Password" />
    <GuestLayout
        class="bg-background flex min-h-svh flex-col items-center justify-center p-6 md:p-10"
    >
        <div class="w-full max-w-md">
            <ForgotPasswordForm :status="status" />
        </div>
    </GuestLayout>
</template>
