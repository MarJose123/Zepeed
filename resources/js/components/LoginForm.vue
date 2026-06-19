<script setup lang="ts">
import { Link, Form, usePage } from "@inertiajs/vue3";
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
import { InputPassword } from "@/components/ui/input-password";
import { Spinner } from "@/components/ui/spinner";
import type { Appearance } from "@/composables/useAppearance";
import { useAppearance } from "@/composables/useAppearance";
import { cn } from "@/lib/utils";

const props = defineProps<{
    class?: HTMLAttributes["class"];
}>();

const { updateAppearance } = useAppearance();
</script>

<template>
    <div :class="cn('flex flex-col gap-6', props.class)">
        <Card class="overflow-hidden p-0">
            <CardContent class="grid p-0 md:grid-cols-2">
                <Form
                    class="p-6 md:p-8"
                    :action="route('login.store', {}, false)"
                    method="post"
                    :reset-on-success="['password']"
                    :reset-on-error="['password']"
                    :on-success="
                        () =>
                            updateAppearance(
                                (usePage()?.props?.appearance
                                    ?.mode as Appearance) || 'light',
                            )
                    "
                    v-slot="{ errors, processing }"
                >
                    <FieldGroup>
                        <div
                            class="flex flex-col items-center gap-2 text-center"
                        >
                            <h1 class="text-2xl font-bold">Welcome back</h1>
                            <p class="text-muted-foreground text-balance">
                                Enter your email and password below to log in
                            </p>
                        </div>
                        <Field>
                            <FieldLabel for="email"> Email </FieldLabel>
                            <Input
                                name="email"
                                id="email"
                                type="email"
                                autofocus
                            />
                            <FieldError v-if="errors.email">{{
                                errors.email
                            }}</FieldError>
                        </Field>
                        <Field>
                            <div class="flex items-center">
                                <FieldLabel for="password">
                                    Password
                                </FieldLabel>
                            </div>
                            <InputPassword id="password" name="password" />
                            <FieldError v-if="errors.password">{{
                                errors.password
                            }}</FieldError>
                        </Field>
                        <Field>
                            <Button type="submit" :disabled="processing">
                                <Spinner v-if="processing" />
                                Login
                            </Button>
                        </Field>
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
            <Link href="#">Terms of Service</Link> and
            <a href="#">Privacy Policy</a>.
        </FieldDescription>
    </div>
</template>
