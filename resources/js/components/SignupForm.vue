<script setup lang="ts">
import { Form, Link } from "@inertiajs/vue3";
import type { HTMLAttributes } from "vue";
import { ref } from "vue";
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
}>();

const passwordRef = ref<InstanceType<typeof InputPasswordStrength> | null>(
    null,
);
</script>

<template>
    <div :class="cn('flex flex-col gap-6', props.class)">
        <Card class="overflow-hidden p-0">
            <CardContent class="grid p-0 md:grid-cols-2">
                <Form
                    class="p-6 md:p-8"
                    method="post"
                    :action="route('register.store')"
                    :reset-on-error="['password', 'password_confirmation']"
                    @error="() => passwordRef?.resetStrength()"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing, clearErrors }"
                >
                    <FieldGroup>
                        <div
                            class="flex flex-col items-center gap-2 text-center"
                        >
                            <h1 class="text-2xl font-bold">
                                Create your account
                            </h1>
                            <p
                                class="text-muted-foreground text-sm text-balance"
                            >
                                Enter your email below to create your account
                            </p>
                        </div>
                        <Field :data-invalid="errors.hasOwnProperty('name')">
                            <FieldLabel for="name"> Full Name </FieldLabel>
                            <Input
                                name="name"
                                id="name"
                                type="text"
                                placeholder="John Doe"
                                @update:modelValue="() => clearErrors('name')"
                            />
                            <FieldError v-if="errors.name">{{
                                errors.name
                            }}</FieldError>
                        </Field>
                        <Field :data-invalid="errors.hasOwnProperty('email')">
                            <FieldLabel for="email"> Email </FieldLabel>
                            <Input
                                name="email"
                                id="email"
                                type="email"
                                placeholder="m@example.com"
                                @update:modelValue="() => clearErrors('email')"
                            />
                            <FieldError v-if="errors.email">{{
                                errors.email
                            }}</FieldError>
                            <FieldDescription>
                                We'll use this to contact you. We will not share
                                your email with anyone else.
                            </FieldDescription>
                        </Field>
                        <Field
                            :data-invalid="errors.hasOwnProperty('password')"
                        >
                            <FieldLabel for="password"> Password </FieldLabel>
                            <InputPasswordStrength
                                id="password"
                                ref="passwordRef"
                                name="password"
                                @update:modelValue="
                                    () => clearErrors('password')
                                "
                            >
                                <template #FieldError>
                                    <FieldError
                                        v-if="errors.password"
                                        class="mb-2"
                                        >{{ errors.password }}</FieldError
                                    >
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
                                id="confirm-password"
                                name="password_confirmation"
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
                                Create Account
                            </Button>
                        </Field>

                        <FieldDescription class="text-center">
                            Already have an account?
                            <Link :href="route('login', {}, false)"
                                >Sign in</Link
                            >
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
