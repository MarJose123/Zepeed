<script setup lang="ts">
import { Form, Head } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldDescription,
} from "@/components/ui/field";
import { InputPassword } from "@/components/ui/input-password";
import { Spinner } from "@/components/ui/spinner";
import GuestLayout from "@/layouts/GuestLayout.vue";
</script>

<template>
    <GuestLayout
        class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10"
    >
        <Head title="Confirm password" />
        <div class="w-full max-w-sm">
            <div class="'flex flex-col gap-6'">
                <Form
                    reset-on-success
                    method="post"
                    :action="route('password.confirm.store')"
                    v-slot="{ errors, processing, clearErrors }"
                >
                    <FieldGroup>
                        <div
                            class="flex flex-col items-center gap-2 text-center"
                        >
                            <h1 class="text-xl font-bold">
                                Confirm your password
                            </h1>
                            <FieldDescription>
                                This is a secure area of the application. Please
                                confirm your password before continuing.
                            </FieldDescription>
                        </div>
                        <Field
                            :data-invalid="errors.hasOwnProperty('password')"
                            class="grid gap-2"
                        >
                            <FieldLabel htmlFor="password">Password</FieldLabel>
                            <InputPassword
                                id="password"
                                name="password"
                                @update:modelValue="
                                    () => clearErrors('password')
                                "
                                autofocus
                            />

                            <FieldError v-if="errors.password">{{
                                errors.password
                            }}</FieldError>
                        </Field>
                        <Field>
                            <Button
                                type="submit"
                                :disabled="processing"
                                data-test="confirm-password-button"
                            >
                                <Spinner v-if="processing" />
                                Confirm Password
                            </Button>
                        </Field>
                    </FieldGroup>
                </Form>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped></style>
