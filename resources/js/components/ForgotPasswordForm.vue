<script setup lang="ts">
import { Form, Link } from "@inertiajs/vue3";
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
import { Spinner } from "@/components/ui/spinner";
import { cn } from "@/lib/utils";

const props = defineProps<{
    class?: HTMLAttributes["class"];
    status?: string;
}>();
</script>

<template>
    <div :class="cn('flex flex-col gap-6', props.class)">
        <Card class="overflow-hidden p-0">
            <CardContent class="grid p-0 md:grid-cols-2">
                <Form
                    class="p-6 md:p-8"
                    method="post"
                    :action="route('password.email')"
                    v-slot="{ processing, errors, recentlySuccessful }"
                >
                    <FieldGroup>
                        <div
                            class="flex flex-col items-center gap-2 text-center"
                        >
                            <h1 class="text-2xl font-bold">Forgot Password</h1>
                            <p
                                class="text-muted-foreground text-sm text-balance"
                            >
                                Enter your email below to request a password
                                reset link.
                            </p>
                        </div>
                        <Field>
                            <FieldLabel for="email"> Email </FieldLabel>
                            <Input
                                name="email"
                                id="email"
                                type="email"
                                autofocus
                                required
                            />
                            <FieldError v-if="errors.email">{{
                                errors.email
                            }}</FieldError>
                            <div
                                v-if="status && recentlySuccessful"
                                class="mb-4 text-center text-sm font-medium text-green-600"
                            >
                                {{ status }}
                            </div>
                        </Field>
                        <Field>
                            <Button type="submit" :disabled="processing">
                                <Spinner v-if="processing" />
                                Send Reset Link
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
