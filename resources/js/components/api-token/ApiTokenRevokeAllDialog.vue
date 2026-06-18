<script setup lang="ts">
import { Form } from "@inertiajs/vue3";
import { useTemplateRef } from "vue";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogClose,
    DialogTrigger,
} from "@/components/ui/dialog";
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";

defineProps<{
    disabled: boolean;
}>();

const passwordInput = useTemplateRef("passwordInput");
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <Button variant="destructive" size="sm" :disabled="disabled">
                Revoke All
            </Button>
        </DialogTrigger>

        <DialogContent class="max-w-md">
            <Form
                method="delete"
                :action="route('api-tokens.revoke-all')"
                reset-on-success
                :options="{ preserveScroll: true }"
                class="space-y-6"
                @error="() => passwordInput?.$el?.focus()"
                v-slot="{ errors, processing, reset, clearErrors }"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle class="text-sm font-medium">
                        Revoke all tokens?
                    </DialogTitle>
                    <DialogDescription>
                        All your API tokens will be immediately invalidated. Any
                        active integrations using these tokens will stop
                        working. Enter your password to confirm.
                    </DialogDescription>
                </DialogHeader>

                <FieldGroup class="grid gap-2">
                    <Field :data-invalid="errors.hasOwnProperty('password')">
                        <FieldLabel for="revoke-all-password" class="sr-only">
                            Password
                        </FieldLabel>
                        <Input
                            ref="passwordInput"
                            id="revoke-all-password"
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

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="
                                () => {
                                    clearErrors();
                                    reset();
                                }
                            "
                        >
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        variant="destructive"
                        size="sm"
                        :disabled="processing"
                    >
                        Revoke All
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>

<style scoped></style>
