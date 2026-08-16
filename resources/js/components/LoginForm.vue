<script setup lang="ts">
import { Form, Link, usePage } from "@inertiajs/vue3";
import { BellRing, CalendarClock, Gauge, ShieldCheck } from "@lucide/vue";
import type { HTMLAttributes } from "vue";
import AppLogoIcon from "@/components/AppLogoIcon.vue";
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
import { recordLoginTime } from "@/composables/useGitHubStarPrompt";
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
                <div
                    class="relative hidden flex-col justify-between gap-12 overflow-hidden bg-primary p-10 text-primary-foreground md:flex"
                >
                    <div
                        class="pointer-events-none absolute -top-24 -right-24 size-64 rounded-full bg-primary-foreground/10 blur-3xl"
                    />
                    <div
                        class="pointer-events-none absolute -bottom-32 -left-16 size-80 rounded-full bg-primary-foreground/10 blur-3xl"
                    />

                    <div class="relative flex flex-col gap-10">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-10 items-center justify-center rounded-xl bg-primary-foreground text-primary"
                            >
                                <AppLogoIcon />
                            </div>
                            <div class="grid gap-0.5">
                                <span
                                    class="text-lg leading-none font-semibold"
                                >
                                    Zepeed
                                </span>
                                <span
                                    class="text-sm text-primary-foreground/70"
                                >
                                    Internet Speed Tracker
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-4">
                            <h2
                                class="text-3xl leading-tight font-bold tracking-tight text-balance"
                            >
                                Your internet speed, on your terms.
                            </h2>
                            <p class="text-pretty text-primary-foreground/80">
                                Zepeed schedules and runs speedtests across
                                multiple providers — Ookla, Fast.com,
                                LibreSpeed, and more — and brings everything
                                into one self-hosted dashboard.
                            </p>
                        </div>
                    </div>

                    <ul
                        class="relative flex flex-col gap-3 text-sm text-primary-foreground/90"
                    >
                        <li class="flex items-center gap-3">
                            <Gauge class="size-4 shrink-0" />
                            Multiple speedtest providers in one place
                        </li>
                        <li class="flex items-center gap-3">
                            <CalendarClock class="size-4 shrink-0" />
                            Automated, scheduled speedtests
                        </li>
                        <li class="flex items-center gap-3">
                            <BellRing class="size-4 shrink-0" />
                            Real-time alerts and webhooks
                        </li>
                        <li class="flex items-center gap-3">
                            <ShieldCheck class="size-4 shrink-0" />
                            Self-hosted — your data stays yours
                        </li>
                    </ul>
                </div>

                <Form
                    class="p-6 md:p-8"
                    :action="route('login.store', {}, false)"
                    method="post"
                    :reset-on-success="['password']"
                    :reset-on-error="['password']"
                    :on-success="
                        () => {
                            recordLoginTime();
                            updateAppearance(
                                (usePage()?.props?.appearance
                                    ?.mode as Appearance) || 'light',
                            );
                        }
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
            </CardContent>
        </Card>
        <FieldDescription class="px-6 text-center">
            By clicking continue, you agree to our
            <Link href="#">Terms of Service</Link> and
            <a href="#">Privacy Policy</a>.
        </FieldDescription>
    </div>
</template>
