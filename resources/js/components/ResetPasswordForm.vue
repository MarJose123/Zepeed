<script setup lang="ts">
import { Form } from "@inertiajs/vue3";
import type { HTMLAttributes } from "vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import {
    InputPassword,
    InputPasswordStrength,
} from "@/components/ui/input-password";
import { Spinner } from "@/components/ui/spinner";
import { cn } from "@/lib/utils";

const props = defineProps<{
    class?: HTMLAttributes["class"];
    email?: string;
    token?: string;
}>();
</script>

<template>
    <div :class="cn('flex flex-col gap-6', props.class)">
        <Card class="overflow-hidden p-0">
            <CardContent class="grid p-0 md:grid-cols-2">
                <Form
                    class="p-6 md:p-8"
                    method="post"
                    :action="route('password.update')"
                    :transform="(data) => ({ ...data, token, email })"
                    v-slot="{ processing, errors, clearErrors }"
                    :reset-on-success="['password', 'password_confirmation']"
                >
                    <FieldGroup>
                        <div
                            class="flex flex-col items-center gap-2 text-center"
                        >
                            <h1 class="text-2xl font-bold">
                                Reset password of your account
                            </h1>
                            <p
                                class="text-muted-foreground text-sm text-balance"
                            >
                                Enter your new password to reset your account
                                credential
                            </p>
                        </div>
                        <Field>
                            <FieldLabel for="email"> Email </FieldLabel>
                            <Input
                                name="email"
                                id="email"
                                type="email"
                                :defaultValue="props.email"
                                readonly
                                disabled
                            />
                        </Field>
                        <Field
                            :data-invalid="errors.hasOwnProperty('password')"
                        >
                            <FieldLabel for="password"> Password </FieldLabel>
                            <InputPasswordStrength
                                name="password"
                                id="password"
                                type="password"
                                @update:modelValue="
                                    () => clearErrors('password')
                                "
                            >
                                <template #FieldError>
                                    <FieldError v-if="errors.password">{{
                                        errors.password
                                    }}</FieldError>
                                </template>
                            </InputPasswordStrength>
                        </Field>
                        <Field
                            :data-invalid="
                                errors.hasOwnProperty('password_confirmation')
                            "
                        >
                            <FieldLabel for="confirm-password">
                                Confirm Password
                            </FieldLabel>
                            <InputPassword
                                name="password_confirmation"
                                id="confirm-password"
                                type="password"
                                @update:modelValue="
                                    () => clearErrors('password_confirmation')
                                "
                            />
                            <FieldDescription
                                >Please confirm your password.</FieldDescription
                            >
                            <FieldError v-if="errors.password_confirmation">{{
                                errors.password_confirmation
                            }}</FieldError>
                        </Field>
                        <Field>
                            <Button type="submit" :disabled="processing">
                                <Spinner v-if="processing" />
                                Reset Password
                            </Button>
                        </Field>
                        <FieldDescription class="text-center">
                            You've remember your password?
                            <a href="#">Sign in</a>
                        </FieldDescription>
                    </FieldGroup>
                </Form>
                <div class="bg-muted relative hidden md:block">
                    <img
                        src="https://www.shadcn-vue.com/placeholder.svg"
                        alt="Image"
                        class="absolute inset-0 h-full w-full object-cover dark:brightness-[0.2] dark:grayscale"
                    />
                </div>
            </CardContent>
        </Card>
        <FieldDescription class="px-6 text-center">
            By clicking continue, you agree to our
            <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
        </FieldDescription>
    </div>
</template>
