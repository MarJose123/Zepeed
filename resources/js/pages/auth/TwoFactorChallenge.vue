<script setup lang="ts">
import { Form, Head } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { Button } from "@/components/ui/button";
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from "@/components/ui/input-otp";
import { Spinner } from "@/components/ui/spinner";
import GuestLayout from "@/layouts/GuestLayout.vue";
import type { TwoFactorConfigContent } from "@/types";

const authConfigContent = computed<TwoFactorConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: "Recovery Code",
            description:
                "Please confirm access to your account by entering one of your emergency recovery codes.",
            buttonText: "login using an authentication code",
        };
    }

    return {
        title: "Authentication Code",
        description:
            "Enter the authentication code provided by your authenticator application.",
        buttonText: "login using a recovery code",
    };
});

const showRecoveryInput = ref<boolean>(false);

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = "";
};

const code = ref<string>("");
</script>

<template>
    <GuestLayout
        class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10"
    >
        <Head title="Confirm password" />
        <div class="w-full max-w-sm">
            <div class="'flex flex-col gap-6'">
                <template v-if="!showRecoveryInput">
                    <Form
                        :action="route('two-factor.login.store')"
                        method="post"
                        class="space-y-4"
                        reset-on-error
                        @error="code = ''"
                        #default="{ errors, processing, clearErrors }"
                    >
                        <FieldGroup>
                            <div
                                class="flex flex-col items-center gap-2 text-center"
                            >
                                <h1 class="text-xl font-bold">
                                    {{ authConfigContent.title }}
                                </h1>
                                <FieldDescription>
                                    {{ authConfigContent.description }}
                                </FieldDescription>
                            </div>
                            <Field>
                                <Input
                                    type="hidden"
                                    name="code"
                                    :value="code"
                                />
                            </Field>
                            <Field
                                :data-invalid="errors.hasOwnProperty('code')"
                            >
                                <InputOTP
                                    id="otp"
                                    class="justify-center"
                                    v-model="code"
                                    :maxlength="6"
                                    :disabled="processing"
                                    autofocus
                                >
                                    <InputOTPGroup>
                                        <InputOTPSlot
                                            v-for="index in 6"
                                            :key="index"
                                            :index="index - 1"
                                        />
                                    </InputOTPGroup>
                                </InputOTP>

                                <FieldError
                                    class="text-center"
                                    v-if="errors.code"
                                    >{{ errors.code }}</FieldError
                                >
                            </Field>
                            <Field>
                                <Button
                                    type="submit"
                                    :disabled="processing"
                                    data-test="confirm-password-button"
                                >
                                    <Spinner v-if="processing" />
                                    Continue
                                </Button>
                            </Field>
                            <div
                                class="text-center text-sm text-muted-foreground"
                            >
                                <span>or you can </span>
                                <button
                                    type="button"
                                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                    @click="
                                        () => toggleRecoveryMode(clearErrors)
                                    "
                                >
                                    {{ authConfigContent.buttonText }}
                                </button>
                            </div>
                        </FieldGroup>
                    </Form>
                </template>

                <template v-else>
                    <Form
                        :action="route('two-factor.login.store')"
                        method="post"
                        class="space-y-4"
                        reset-on-error
                        @error="code = ''"
                        #default="{ errors, processing, clearErrors }"
                    >
                        <FieldGroup>
                            <div
                                class="flex flex-col items-center gap-2 text-center"
                            >
                                <h1 class="text-xl font-bold">
                                    {{ authConfigContent.title }}
                                </h1>
                                <FieldDescription>
                                    {{ authConfigContent.description }}
                                </FieldDescription>
                            </div>
                            <Field
                                :data-invalid="
                                    errors.hasOwnProperty('recovery_code')
                                "
                                class="grid gap-2"
                            >
                                <Input
                                    name="recovery_code"
                                    type="text"
                                    placeholder="Enter recovery code"
                                    :autofocus="showRecoveryInput"
                                    required
                                />

                                <FieldError v-if="errors.recovery_code">{{
                                    errors.recovery_code
                                }}</FieldError>
                            </Field>
                            <Field>
                                <Button
                                    type="submit"
                                    :disabled="processing"
                                    data-test="confirm-password-button"
                                >
                                    <Spinner v-if="processing" />
                                    Continue
                                </Button>
                            </Field>
                            <div
                                class="text-center text-sm text-muted-foreground"
                            >
                                <span>or you can </span>
                                <button
                                    type="button"
                                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                    @click="
                                        () => toggleRecoveryMode(clearErrors)
                                    "
                                >
                                    {{ authConfigContent.buttonText }}
                                </button>
                            </div>
                        </FieldGroup>
                    </Form>
                </template>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped></style>
