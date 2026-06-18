<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { Loader2 } from "@lucide/vue";
import { useTemplateRef, watch } from "vue";
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import { InputPassword } from "@/components/ui/input-password";
import type { ApiToken } from "@/types/api-token";

const props = defineProps<{ token: ApiToken | null }>();
const emit = defineEmits<{ close: [] }>();

const passwordInput = useTemplateRef("passwordInput");

const form = useForm({ password: "" });

watch(
    () => props.token,
    (val) => {
        if (!val) {
            form.reset();
            form.clearErrors();
        }
    },
);

function close(): void {
    emit("close");
}

function confirm(): void {
    if (!props.token) return;

    form.delete(route("api-tokens.destroy", { token: props.token.id }), {
        preserveScroll: true,
        onSuccess: () => close(),
        onError: () => passwordInput.value?.$el?.focus(),
    });
}
</script>

<template>
    <AlertDialog :open="!!props.token" @update:open="(v) => !v && close()">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle class="text-sm font-medium">
                    Revoke "{{ props.token?.name }}"?
                </AlertDialogTitle>
                <AlertDialogDescription>
                    Any API requests using this token will immediately fail.
                    Enter your password to confirm.
                </AlertDialogDescription>
            </AlertDialogHeader>

            <FieldGroup class="grid gap-2">
                <Field :data-invalid="form.errors.hasOwnProperty('password')">
                    <FieldLabel for="revoke-password" class="sr-only">
                        Password
                    </FieldLabel>
                    <InputPassword
                        ref="passwordInput"
                        id="revoke-password"
                        name="password"
                        placeholder="Confirm your password"
                        v-model="form.password"
                        @update:model-value="form.clearErrors('password')"
                        @keyup.enter="confirm"
                    />
                    <FieldError v-if="form.errors.password">
                        {{ form.errors.password }}
                    </FieldError>
                </Field>
            </FieldGroup>

            <AlertDialogFooter class="gap-2">
                <AlertDialogCancel as-child>
                    <Button variant="secondary" size="sm" @click="close">
                        Cancel
                    </Button>
                </AlertDialogCancel>
                <Button
                    variant="destructive"
                    size="sm"
                    :disabled="form.processing"
                    @click="confirm"
                >
                    <Loader2
                        v-if="form.processing"
                        class="mr-1.5 h-3 w-3 animate-spin"
                    />
                    Revoke
                </Button>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

<style scoped></style>
