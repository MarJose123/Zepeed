<script setup lang="ts">
import { Form } from "@inertiajs/vue3";
import { useTemplateRef } from "vue";
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogCancel,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import type { ApiToken } from "@/types/api-token";

const props = defineProps<{ token: ApiToken | null }>();
const emit = defineEmits<{ close: [] }>();

const passwordInput = useTemplateRef("passwordInput");
</script>

<template>
    <AlertDialog
        :open="!!props.token"
        @update:open="(v) => !v && emit('close')"
    >
        <AlertDialogContent>
            <Form
                method="delete"
                :action="
                    route('api-tokens.destroy', { token: props.token?.id })
                "
                reset-on-success
                :options="{ preserveScroll: true }"
                class="space-y-6"
                @error="() => passwordInput?.$el?.focus()"
                @success="emit('close')"
                v-slot="{ errors, processing, reset, clearErrors }"
            >
                <AlertDialogHeader class="space-y-3">
                    <AlertDialogTitle class="text-sm font-medium">
                        Revoke "{{ props.token?.name }}"?
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        Any API requests using this token will immediately fail.
                        Enter your password to confirm.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <FieldGroup class="grid gap-2">
                    <Field :data-invalid="errors.hasOwnProperty('password')">
                        <FieldLabel for="revoke-password" class="sr-only">
                            Password
                        </FieldLabel>
                        <Input
                            ref="passwordInput"
                            id="revoke-password"
                            name="password"
                            type="password"
                            placeholder="Confirm your password"
                            class="text-xs"
                            @update:model-value="() => clearErrors('password')"
                        />
                        <FieldError v-if="errors.password">
                            {{ errors.password }}
                        </FieldError>
                    </Field>
                </FieldGroup>

                <AlertDialogFooter class="gap-2">
                    <AlertDialogCancel as-child>
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="
                                () => {
                                    clearErrors();
                                    reset();
                                    emit('close');
                                }
                            "
                        >
                            Cancel
                        </Button>
                    </AlertDialogCancel>
                    <Button
                        type="submit"
                        variant="destructive"
                        size="sm"
                        :disabled="processing"
                    >
                        Revoke
                    </Button>
                </AlertDialogFooter>
            </Form>
        </AlertDialogContent>
    </AlertDialog>
</template>

<style scoped></style>
