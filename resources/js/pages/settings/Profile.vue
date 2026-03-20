<script setup lang="ts">
import { Form, Head, Link, usePage } from "@inertiajs/vue3";
import DeleteUser from "@/components/DeleteUser.vue";
import { Button } from "@/components/ui/button";
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { Spinner } from "@/components/ui/spinner";
import AppLayout from "@/layouts/AppLayout.vue";
import SettingsLayout from "@/layouts/SettingsLayout.vue";
import type { TBreadcrumbItem } from "@/types";

type Props = {
    mustVerifyEmail: boolean;
};

defineProps<Props>();

const breadcrumbItems: TBreadcrumbItem[] = [
    {
        title: "Settings",
        href: route("profile.edit", {}, false),
    },
    {
        title: "Profile",
        href: route("profile.edit", {}, false),
    },
];

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile Settings" />

        <h1 class="sr-only">Profile Settings</h1>

        <SettingsLayout>
            <Form
                class="space-y-6 max-w-lg"
                method="patch"
                :action="route('profile.update')"
                v-slot="{ processing, errors, clearErrors }"
            >
                <FieldGroup>
                    <Field :data-invalid="errors.hasOwnProperty('name')">
                        <FieldLabel for="name">Name</FieldLabel>
                        <Input
                            name="name"
                            id="name"
                            type="text"
                            :default-value="user.name"
                            autocomplete="name"
                            placeholder="Full name"
                            @update:modelValue="() => clearErrors('name')"
                        />
                        <FieldError v-if="errors.name">{{
                            errors.name
                        }}</FieldError>
                    </Field>
                    <Field :data-invalid="errors.hasOwnProperty('email')">
                        <FieldLabel for="email">Email</FieldLabel>
                        <Input
                            name="email"
                            id="email"
                            type="email"
                            :default-value="user.email"
                            autocomplete="email"
                            placeholder="Email Address"
                            @update:modelValue="() => clearErrors('email')"
                        />
                        <FieldError v-if="errors.name">{{
                            errors.name
                        }}</FieldError>
                        <FieldDescription
                            v-if="mustVerifyEmail && !user.email_verified_at"
                        >
                            Your email address is unverified.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </FieldDescription>
                    </Field>
                </FieldGroup>
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Save Changes
                </Button>
            </Form>

            <DeleteUser class="max-w-lg" />
        </SettingsLayout>
    </AppLayout>
</template>

<style scoped></style>
