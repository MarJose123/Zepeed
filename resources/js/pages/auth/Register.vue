<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import SignupForm from "@/components/SignupForm.vue";
import GuestLayout from "@/layouts/GuestLayout.vue";
</script>
<template>
    <Head title="Register" />
    <GuestLayout
        class="bg-muted flex min-h-svh flex-col items-center justify-center p-6 md:p-10"
    >
        <div class="w-full max-w-sm md:max-w-4xl">
            <SignupForm />
        </div>
    </GuestLayout>
</template>
